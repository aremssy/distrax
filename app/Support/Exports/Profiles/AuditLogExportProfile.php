<?php

namespace App\Support\Exports\Profiles;

use App\Models\AuditLog;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'audit-logs';
    }

    public function permission(): string
    {
        return 'audit_logs.view';
    }

    public function title(): string
    {
        return 'Audit Logs';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'admin' => 'Admin',
            'action' => 'Action',
            'model' => 'Model',
            'model_id' => 'Model ID',
            'ip_address' => 'IP Address',
            'created_at' => 'Date',
        ];
    }

    public function perPage(): int
    {
        return 50;
    }

    /**
     * Mirrors AuditLogController@index filters.
     */
    public function query(Request $request): Builder
    {
        return AuditLog::with('admin:id,name,email')
            ->when($request->integer('admin_id'), fn ($query, int $adminId) => $query->where('admin_id', $adminId))
            ->when($request->string('action')->value(), fn ($query, string $action) => $query->where('action', 'like', "%{$action}%"))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at');
    }

    /**
     * @param  AuditLog  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'admin' => $row->admin?->name,
            'action' => $row->action,
            'model' => $row->model ? class_basename((string) $row->model) : null,
            'model_id' => $row->model_id,
            'ip_address' => $row->ip_address,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
