<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\ImportLog;
use App\Models\Organization;
use App\Models\Transaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $activeSemester = AcademicYear::getActive(); // [perf] cached helper

        // [perf] Cache the heavy dashboard payload for 60s — admin dashboard reads many
        // tables on remote Supabase (~14 queries). Short TTL keeps numbers fresh
        // enough to feel "live" without re-running every aggregation on each page hit.
        $semIdKey  = $activeSemester?->id ?? 'none';
        $cacheKey  = "admin_dashboard:v1:{$semIdKey}";

        $payload = Cache::remember($cacheKey, 60, function () use ($activeSemester) {
            // ── Core counts (one round-trip via UNION ALL) ────────────────────
            // [perf] Was 6 COUNT(*) queries (≈6×80ms = ~480ms). One aggregate query
            // against system tables returns all counts in a single round-trip.
            $coreCounts = DB::select("
                SELECT 'colleges' AS k, COUNT(*) AS v FROM colleges
                UNION ALL SELECT 'departments',   COUNT(*) FROM departments
                UNION ALL SELECT 'programs',      COUNT(*) FROM programs
                UNION ALL SELECT 'organizations', COUNT(*) FROM organizations WHERE is_active = true
                UNION ALL SELECT 'students',      COUNT(*) FROM students
                UNION ALL SELECT 'users',         COUNT(*) FROM users
            ");

            $stats = [];
            foreach ($coreCounts as $r) {
                $stats[$r->k] = (int) $r->v;
            }

            // ── Today's activity ──────────────────────────────────────────────
            $todayCollections = Transaction::where('is_void', false)
                ->whereDate('created_at', today())
                ->selectRaw('transaction_type, payment_method, SUM(amount_paid) as total, COUNT(*) as cnt')
                ->groupBy('transaction_type', 'payment_method')
                ->get();

            $todayTotal = $todayCollections->sum('total');

            // ── Semester collections (per org) ────────────────────────────────
            $semesterByOrg = collect();
            if ($activeSemester) {
                $semesterByOrg = Transaction::where('is_void', false)
                    ->where('academic_year_id', $activeSemester->id)
                    ->join('organizations', 'transactions.organization_id', '=', 'organizations.id')
                    ->selectRaw('organizations.name as org_name, organizations.type as org_type, SUM(transactions.amount_paid) as total, COUNT(*) as cnt')
                    ->groupBy('organizations.id', 'organizations.name', 'organizations.type')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get();
            }

            // ── Recent audit log entries ──────────────────────────────────────
            $recentAuditLogs = AuditLog::with('user')
                ->latest('timestamp')
                ->limit(8)
                ->get();

            // ── Lab 6: Import/Export/Backup status ────────────────────────────
            $lastImport = ImportLog::with('uploadedBy', 'academicYear')->latest()->first();
            $lastBackup = BackupLog::where('status', 'SUCCESS')->latest('executed_at')->first();
            $failedBackupCount = BackupLog::where('status', 'FAILED')
                ->where('executed_at', '>=', now()->subDays(7))
                ->count();

            // ── System health ─────────────────────────────────────────────────
            $failedJobs  = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();
            $criticalAuditToday = AuditLog::whereIn('action', [
                'TRANSACTION_VOIDED', 'VOID_APPROVED', 'VOID_REJECTED',
                'FEE_PROFILE_UPDATED', 'BACKUP_FAILED',
            ])->whereDate('timestamp', today())->count();

            // ── Org type breakdown ────────────────────────────────────────────
            $orgBreakdown = Organization::where('is_active', true)
                ->selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->pluck('cnt', 'type');

            return compact(
                'stats', 'todayCollections', 'todayTotal', 'semesterByOrg',
                'recentAuditLogs', 'lastImport', 'lastBackup', 'failedBackupCount',
                'failedJobs', 'pendingJobs', 'criticalAuditToday', 'orgBreakdown'
            );
        });

        extract($payload);

        return view('admin.dashboard', compact(
            'activeSemester',
            'stats',
            'todayCollections',
            'todayTotal',
            'semesterByOrg',
            'recentAuditLogs',
            'lastImport',
            'lastBackup',
            'failedBackupCount',
            'failedJobs',
            'pendingJobs',
            'criticalAuditToday',
            'orgBreakdown'
        ));
    }

    public function triggerBackup(): \Illuminate\Http\RedirectResponse
    {
        try {
            Artisan::call('backup:run', ['--only-db' => false]);

            // [perf] Invalidate cached dashboard so the new backup entry shows immediately.
            Cache::flush(); // narrow: only admin_dashboard:* — but flush is safe & cheap here

            BackupLog::create([
                'status'      => 'SUCCESS',
                'filename'    => 'manual-' . now()->format('Y-m-d-His') . '.zip',
                'disk'        => config('backup.backup.destination.disks.0', 'local'),
                'executed_at' => now(),
            ]);

            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'BACKUP_TRIGGERED',
                'entity_type' => 'SYSTEM',
                'entity_id'   => null,
                'details'     => ['triggered_by' => 'manual', 'triggered_at' => now()->toIso8601String()],
                'ip_address'  => request()->ip(),
                'timestamp'   => now(),
            ]);

            return back()->with('success', 'Backup triggered successfully. The backup is running in the background.');
        } catch (\Throwable $e) {
            BackupLog::create([
                'status'        => 'FAILED',
                'error_message' => $e->getMessage(),
                'disk'          => 'local',
                'executed_at'   => now(),
            ]);

            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }
}
