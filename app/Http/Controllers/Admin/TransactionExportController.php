<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TransactionExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ExportLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TransactionExportController extends Controller
{
    private const QUEUE_THRESHOLD = 10000;

    public function index()
    {
        $academicYears  = AcademicYear::orderByDesc('school_year')->get();
        $organizations  = Organization::where('is_active', true)->orderBy('name')->get();
        $recentExports  = ExportLog::with('requestedBy')
            ->where('requested_by_user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.exports.transactions', compact('academicYears', 'organizations', 'recentExports'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'organization_id'  => 'required|exists:organizations,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'transaction_type' => 'nullable|in:FEE,FINE',
            'payment_method'   => 'nullable|in:CASH,GCASH',
            'format'           => 'required|in:xlsx,csv',
            'include_voided'   => 'nullable|boolean',
        ]);

        $orgId    = $validated['organization_id'];
        $rowCount = \App\Models\Transaction::where('organization_id', $orgId)->count();

        $exportLog = ExportLog::create([
            'type'                  => 'TRANSACTIONS',
            'requested_by_user_id'  => auth()->id(),
            'filters'               => $validated,
            'format'                => strtoupper($validated['format']),
            'status'                => 'PROCESSING',
            'row_count'             => $rowCount,
        ]);

        $export = new TransactionExport(
            organizationId:   $orgId,
            academicYearId:   $validated['academic_year_id'] ?? null,
            startDate:        $validated['start_date'] ?? null,
            endDate:          $validated['end_date'] ?? null,
            transactionType:  $validated['transaction_type'] ?? null,
            paymentMethod:    $validated['payment_method'] ?? null,
            includeVoided:    (bool) ($validated['include_voided'] ?? false),
        );

        $filename = 'transactions_' . now()->format('Ymd_His') . '.' . $validated['format'];

        $exportLog->update(['status' => 'READY', 'download_path' => 'exports/' . $filename]);

        \App\Models\AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'EXPORT_GENERATED',
            'entity_type' => 'EXPORT_LOG',
            'entity_id'   => $exportLog->id,
            'details'     => ['type' => 'TRANSACTIONS', 'format' => strtoupper($validated['format']), 'filters' => $validated],
            'ip_address'  => request()->ip(),
            'timestamp'   => now(),
        ]);

        return Excel::download($export, $filename);
    }
}
