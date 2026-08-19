<?php

namespace App\Support\Exports\Profiles;

use App\Models\MaintenanceRequest;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MaintenanceRequestExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'maintenance-requests';
    }

    public function permission(): string
    {
        return 'technician_bookings.view';
    }

    public function title(): string
    {
        return 'Maintenance Requests';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'tenant' => 'Tenant',
            'owner' => 'Owner',
            'technician' => 'Technician',
            'listing' => 'Property',
            'priority' => 'Priority',
            'status' => 'Status',
            'resolved_at' => 'Resolved',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors MaintenanceRequestController@index filters.
     */
    public function query(Request $request): Builder
    {
        return MaintenanceRequest::with(['tenant:id,name,email', 'owner:id,name,email', 'technician.user:id,name,email', 'listing:id,title'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('priority')->value(), fn ($query, string $priority) => $query->where('priority', $priority))
            ->latest();
    }

    /**
     * @param  MaintenanceRequest  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'tenant' => $row->tenant?->name,
            'owner' => $row->owner?->name,
            'technician' => $row->technician?->user?->name,
            'listing' => $row->listing?->title,
            'priority' => $row->priority,
            'status' => $row->status,
            'resolved_at' => $row->resolved_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
