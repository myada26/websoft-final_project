<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\StudentFine;
use App\Services\FineCollectionWindowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class FineCollectionWindowController extends Controller
{
    public function __construct(private readonly FineCollectionWindowService $windows)
    {
    }

    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;
        $activeSemester = AcademicYear::getActive(); // [perf] cached helper
        $window = $this->windows->getWindow($organization->id);

        $stats = [
            'unpaid_count' => 0,
            'unpaid_total' => 0,
            'paid_count' => 0,
            'paid_total' => 0,
        ];

        if ($activeSemester) {
            // [perf] 4 round-trips collapsed into 1 conditional aggregate (Postgres
            // FILTER syntax). Was ~4×80ms vs remote Supabase; now a single query.
            $row = StudentFine::where('organization_id', $organization->id)
                ->where('academic_year_id', $activeSemester->id)
                ->selectRaw("
                    COUNT(*) FILTER (WHERE status = 'UNPAID') AS unpaid_count,
                    COALESCE(SUM(fine_amount) FILTER (WHERE status = 'UNPAID'), 0) AS unpaid_total,
                    COUNT(*) FILTER (WHERE status = 'PAID')   AS paid_count,
                    COALESCE(SUM(fine_amount) FILTER (WHERE status = 'PAID'),   0) AS paid_total
                ")
                ->first();

            $stats['unpaid_count'] = (int)   ($row->unpaid_count ?? 0);
            $stats['unpaid_total'] = (float) ($row->unpaid_total ?? 0);
            $stats['paid_count']   = (int)   ($row->paid_count   ?? 0);
            $stats['paid_total']   = (float) ($row->paid_total   ?? 0);
        }

        return view('org.fine-windows.index', compact('organization', 'activeSemester', 'window', 'stats'));
    }

    public function open(Request $request)
    {
        $organization = auth()->user()->organization;

        try {
            $window = $this->windows->openWindow($organization, auth()->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        $this->log($request, 'FINE_WINDOW_OPENED', [
            'window_id' => $window->id,
            'academic_year_id' => $window->academic_year_id,
        ]);

        return back()->with('success', 'Fine collection window opened.');
    }

    public function close(Request $request)
    {
        $organization = auth()->user()->organization;

        try {
            $window = $this->windows->closeWindow($organization, auth()->user());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        $this->log($request, 'FINE_WINDOW_CLOSED', [
            'window_id' => $window->id,
            'academic_year_id' => $window->academic_year_id,
        ]);

        return back()->with('success', 'Fine collection window closed.');
    }

    private function log(Request $request, string $action, array $details): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => 'FINE_COLLECTION_WINDOW',
            'entity_id' => $details['window_id'] ?? null,
            'details' => $details,
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);
    }
}
