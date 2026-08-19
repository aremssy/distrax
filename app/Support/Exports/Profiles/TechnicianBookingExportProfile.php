<?php

namespace App\Support\Exports\Profiles;

use App\Models\TechnicianBooking;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TechnicianBookingExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'tech-bookings';
    }

    public function permission(): string
    {
        return 'technician_bookings.view';
    }

    public function title(): string
    {
        return 'Technician Bookings';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'user' => 'Customer',
            'technician' => 'Technician',
            'category' => 'Category',
            'scheduled_at' => 'Scheduled',
            'agreed_amount' => 'Agreed Amount',
            'is_urgent' => 'Urgent',
            'status' => 'Status',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors TechnicianBookingController@index filters.
     */
    public function query(Request $request): Builder
    {
        return TechnicianBooking::with(['technician.user:id,name,email', 'technician.category:id,name,commission_rate', 'user:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->boolean('urgent'), fn ($query) => $query->where('is_urgent', true))
            ->latest();
    }

    /**
     * @param  TechnicianBooking  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'user' => $row->user?->name,
            'technician' => $row->technician?->user?->name,
            'category' => $row->technician?->category?->name,
            'scheduled_at' => $row->scheduled_at?->toDateTimeString(),
            'agreed_amount' => $row->agreed_amount,
            'is_urgent' => $row->is_urgent ? 'Yes' : 'No',
            'status' => $row->status,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
