<?php

namespace App\Policies;

use App\Models\HotelBooking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HotelBookingPolicy
{
    /**
     * The guest who booked, or the owner of the booked listing, may view it.
     * Denials read as "not found" so the API does not disclose other people's bookings.
     */
    public function view(User $user, HotelBooking $booking): Response
    {
        $isGuest = $booking->user_id === $user->id;
        $isListingOwner = $booking->listing?->owner_id === $user->id;

        return $isGuest || $isListingOwner
            ? Response::allow()
            : Response::denyAsNotFound('Booking not found.');
    }

    /** Only the guest who made the booking may cancel it. */
    public function cancel(User $user, HotelBooking $booking): Response
    {
        return $booking->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound('Booking not found.');
    }
}
