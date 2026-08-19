<?php

namespace App\Listeners;

use App\Events\BookingStatusUpdated;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBookingNotification implements ShouldQueue
{
    /**
     * Dispatch only after the surrounding DB transaction commits, so the queue
     * worker never reads a booking that has not yet been persisted.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    public function handle(BookingStatusUpdated $event): void
    {
        $booking = $event->booking;
        $listing = $booking->listing ?? $booking->listing()->first();

        if (! $listing) {
            return;
        }

        $owner = User::find($listing->owner_id);

        if (! $owner) {
            return;
        }

        match ($booking->status) {
            'pending' => $this->dispatcher->send(
                user: $owner,
                type: 'booking_created',
                title: 'New booking request',
                body: "A guest booked {$listing->title} from {$booking->check_in->toDateString()} to {$booking->check_out->toDateString()}.",
                data: ['booking_id' => $booking->id, 'listing_id' => $listing->id],
                notifiable: $booking,
            ),
            'cancelled' => $this->dispatcher->send(
                user: $owner,
                type: 'booking_update',
                title: 'Booking cancelled',
                body: "A guest cancelled their booking for {$listing->title}.",
                data: ['booking_id' => $booking->id, 'listing_id' => $listing->id],
                notifiable: $booking,
            ),
            default => null,
        };
    }
}
