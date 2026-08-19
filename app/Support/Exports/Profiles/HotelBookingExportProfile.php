<?php

namespace App\Support\Exports\Profiles;

use App\Models\HotelBooking;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class HotelBookingExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'hotel-bookings';
    }

    public function permission(): string
    {
        return 'hotel_bookings.view';
    }

    public function title(): string
    {
        return 'Hotel Bookings';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'user' => 'Guest',
            'listing' => 'Property',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'guests' => 'Guests',
            'amount' => 'Amount',
            'status' => 'Status',
            'created_at' => 'Booked',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors HotelBookingController@index filters.
     */
    public function query(Request $request): Builder
    {
        return HotelBooking::with(['listing:id,title', 'user:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest();
    }

    /**
     * @param  HotelBooking  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'user' => $row->user?->name,
            'listing' => $row->listing?->title,
            'check_in' => $row->check_in?->toDateString(),
            'check_out' => $row->check_out?->toDateString(),
            'guests' => $row->guests,
            'amount' => $row->amount,
            'status' => $row->status,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
