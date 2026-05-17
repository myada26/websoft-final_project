<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ImportLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')
            ->when(request('search'), fn($q, $s) => $q->where('action','like',"%$s%")->orWhereHas('user', fn($u) => $u->where('username','like',"%$s%")))
            ->when(request('action'), fn($q, $a) => $q->where('action', $a))
            ->orderByDesc('timestamp')
            ->paginate(50);

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog): \Illuminate\View\View
    {
        $auditLog->load('user');
        $details   = $auditLog->details ?? [];
        $oldValues = $details['original_values'] ?? null;
        $newValues = $details['changed_fields']  ?? null;

        // If this entry references an ImportLog (entity_type=IMPORT_LOG), load it so
        // the view can offer a "Download Failure Report" button for rejected rows.
        $relatedImportLog = null;
        if ($auditLog->entity_type === 'IMPORT_LOG' && $auditLog->entity_id) {
            $relatedImportLog = ImportLog::find($auditLog->entity_id);
        }

        return view('admin.audit-logs.show', compact(
            'auditLog', 'oldValues', 'newValues', 'details', 'relatedImportLog'
        ));
    }
}
