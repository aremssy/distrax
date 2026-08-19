<?php

namespace App\Events;

use App\Models\HotelBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly HotelBooking $booking,
        public readonly string $previousStatus,
    ) {}
}
