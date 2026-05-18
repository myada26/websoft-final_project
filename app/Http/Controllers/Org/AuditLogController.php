<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = \App\Models\AuditLog::whereHas('user', fn($q) => $q->where('organization_id', auth()->user()->organization_id))
            ->with('user')
            ->orderByDesc('timestamp')
            ->paginate(50);

        return view('org.audit-logs.index', compact('logs'));
    }
}
