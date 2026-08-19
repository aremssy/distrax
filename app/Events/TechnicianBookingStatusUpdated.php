<?php

namespace App\Events;

use App\Models\TechnicianBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianBookingStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TechnicianBooking $booking,
        public readonly string $previousStatus,
    ) {}
}
