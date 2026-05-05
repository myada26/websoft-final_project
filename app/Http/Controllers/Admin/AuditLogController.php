<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')
            ->when(request('search'), fn($q, $s) => $q->where('action','like',"%$s%")->orWhereHas('user', fn($u) => $u->where('username','like',"%$s%")))
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
