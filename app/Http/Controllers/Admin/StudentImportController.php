<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FailedImportExport;
use App\Exports\StudentEnrollmentTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentEnrollmentImport;
use App\Jobs\ProcessStudentEnrollmentImport;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    private const QUEUE_THRESHOLD = 1000;

    public function downloadTemplate()
    {
        return Excel::download(new StudentEnrollmentTemplateExport(), 'enrollment_template.xlsx');
    }

    public function index()
    {
        $logs = ImportLog::with('uploadedBy', 'academicYear')
            ->latest()
            ->paginate(20);

        return view('admin.imports.index', compact('logs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return back()->with('error', 'No active academic year. Please set one before importing.');
        }

        $uploadedFilename = $request->file('file')->getClientOriginalName();
        $path             = $request->file('file')->store('imports');

        $importLog = ImportLog::create([
            'type'                => 'STUDENT_ENROLLMENT',
            'filename'            => $uploadedFilename,
            'uploaded_by_user_id' => auth()->id(),
            'academic_year_id'    => $activeYear->id,
            'rows_processed'      => 0,
            'failures_count'      => 0,
            'status'              => 'PROCESSING',
            'started_at'          => now(),
        ]);

        // ── Decide: inline vs. queued ─────────────────────────────────────────
        // Tiny files (< QUEUE_THRESHOLD rows) finish faster inline than the queue
        // overhead (worker poll + framework bootstrap + status polling lag).
        // Only fall back to the queue for genuinely large files where HTTP
        // timeout becomes a real risk.
        $rowEstimate = $this->estimateRowCount($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());

        if ($rowEstimate >= self::QUEUE_THRESHOLD) {
            $importLog->update(['status' => 'PENDING']);

            ProcessStudentEnrollmentImport::dispatch(
                $path,
                auth()->id(),
                $importLog->id,
                'UNIVERSITY_WIDE'
            );

            $msg = "Large file detected ({$rowEstimate}+ rows). Queued as Import Log #{$importLog->id}.";

            return $request->expectsJson()
                ? response()->json(['import_log_id' => $importLog->id, 'status' => 'PENDING', 'message' => $msg])
                : redirect()->route('admin.imports.index')->with('success', $msg);
        }

        // ── Inline path ───────────────────────────────────────────────────────
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $import = new StudentEnrollmentImport('UNIVERSITY_WIDE');

        try {
            Excel::import($import, Storage::path($path));
        } catch (\Throwable $e) {
            $importLog->update([
                'status'          => 'FAILED',
                'completed_at'    => now(),
                'failure_details' => [['row' => 0, 'errors' => [$e->getMessage()], 'values' => []]],
            ]);
            Storage::delete($path);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $rowCount     = $import->getRowCount();
        $failures     = $import->failures();
        $failureCount = count($failures);

        $status = match (true) {
            $failureCount === 0 && $rowCount > 0 => 'SUCCESS',
            $rowCount === 0                       => 'FAILED',
            default                               => 'PARTIAL',
        };

        $importLog->update([
            'rows_processed'  => $rowCount,
            'failures_count'  => $failureCount,
            'failure_details' => $failureCount > 0 ? $failures : null,
            'status'          => $status,
            'completed_at'    => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'STUDENT_IMPORT_COMPLETED',
            'entity_type' => 'IMPORT_LOG',
            'entity_id'   => $importLog->id,
            'details'     => [
                'filename'       => $uploadedFilename,
                'rows_processed' => $rowCount,
                'failures_count' => $failureCount,
                'status'         => $status,
            ],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        Storage::delete($path);

        $msg = match ($status) {
            'SUCCESS' => "Imported {$rowCount} student(s) successfully.",
            'PARTIAL' => "Imported {$rowCount} student(s); {$failureCount} row(s) rejected. Download the failure report from the audit log entry.",
            default   => "Import failed. {$failureCount} row(s) rejected.",
        };

        if ($request->expectsJson()) {
            return response()->json([
                'import_log_id'      => $importLog->id,
                'status'             => $status,
                'rows_processed'     => $rowCount,
                'failures_count'     => $failureCount,
                'message'            => $msg,
                'failure_report_url' => $failureCount > 0 ? route('admin.imports.failure-report', $importLog) : null,
            ]);
        }

        return redirect()->route('admin.imports.index')
            ->with($status === 'SUCCESS' ? 'success' : 'warning', $msg);
    }

    /**
     * Cheap row-count estimate to decide inline vs. queue.
     * For CSV: line-count via fgets. For xlsx/xls: skip (return 0) — most
     * spreadsheets are tiny in practice; queue path is the fallback only when
     * we're sure the file is large.
     */
    private function estimateRowCount(string $absolutePath, string $extension): int
    {
        if (! in_array(strtolower($extension), ['csv', 'txt'], true)) {
            return 0;
        }
        $count  = 0;
        $handle = @fopen($absolutePath, 'r');
        if (! $handle) return 0;
        while (! feof($handle) && fgets($handle) !== false) {
            $count++;
            if ($count > self::QUEUE_THRESHOLD + 1) break;
        }
        fclose($handle);
        // Subtract header row
        return max(0, $count - 1);
    }

    public function status(ImportLog $importLog)
    {
        return response()->json([
            'status'         => $importLog->status,
            'rows_processed' => $importLog->rows_processed,
            'failures_count' => $importLog->failures_count,
            'completed_at'   => $importLog->completed_at?->toISOString(),
            'can_download'   => $importLog->failures_count > 0 && !empty($importLog->failure_details),
            'download_url'   => ($importLog->failures_count > 0 && !empty($importLog->failure_details))
                ? route('admin.imports.failure-report', $importLog)
                : null,
        ]);
    }

    public function downloadFailureReport(ImportLog $importLog, Request $request)
    {
        if ($importLog->failures_count === 0 || empty($importLog->failure_details)) {
            return back()->with('error', 'This import has no failure details to download.');
        }

        $format    = strtolower($request->query('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writer    = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        $filename = sprintf(
            'import-%d-failures-%s.%s',
            $importLog->id,
            now()->format('Ymd-His'),
            $extension
        );

        return Excel::download(new FailedImportExport($importLog), $filename, $writer);
    }
}
