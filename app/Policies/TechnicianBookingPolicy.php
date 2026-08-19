<?php

namespace App\Policies;

use App\Models\TechnicianBooking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TechnicianBookingPolicy
{
    /**
     * Both sides of the job — the customer and the assigned technician — may view it.
     * Denials read as "not found" so the API does not disclose other people's bookings.
     */
    public function view(User $user, TechnicianBooking $booking): Response
    {
        $isCustomer = $booking->user_id === $user->id;
        $isAssignedTechnician = $booking->technician?->user_id === $user->id;

        return $isCustomer || $isAssignedTechnician
            ? Response::allow()
            : Response::denyAsNotFound('Booking not found.');
    }

    /** Only the customer decides on a quote. */
    public function respondToQuote(User $user, TechnicianBooking $booking): Response
    {
        return $booking->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound('Booking not found.');
    }
}
