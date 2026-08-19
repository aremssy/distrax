<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('admin:id,name,email')
            ->when($request->integer('admin_id'), fn ($query, int $adminId) => $query->where('admin_id', $adminId))
            ->when($request->string('action')->value(), fn ($query, string $action) => $query->where('action', 'like', "%{$action}%"))
            ->when(rescue(fn () => $request->date('from'), null, false), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when(rescue(fn () => $request->date('to'), null, false), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(50);

        $admins = User::whereHas('auditLogs')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', compact('logs', 'admins'));
    }
}
