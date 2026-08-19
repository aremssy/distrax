<?php

namespace App\Listeners;

use App\Events\TechnicianBookingStatusUpdated;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTechnicianBookingNotification implements ShouldQueue
{
    /**
     * Dispatch only after the surrounding DB transaction commits, so the queue
     * worker never reads a booking that has not yet been persisted.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    public function handle(TechnicianBookingStatusUpdated $event): void
    {
        $booking = $event->booking;
        $previous = $event->previousStatus;

        $technicianUser = User::find($booking->technician?->user_id);
        $customer = User::find($booking->user_id);

        // New booking created → notify technician
        if ($booking->status === 'pending' && $previous === 'new') {
            if ($technicianUser) {
                $description = mb_strimwidth($booking->description ?? '', 0, 80, '…');
                $this->dispatcher->send(
                    user: $technicianUser,
                    type: 'booking_created',
                    title: 'New service request',
                    body: "You have a new booking: {$description}",
                    data: ['booking_id' => $booking->id],
                    notifiable: $booking,
                );
            }

            return;
        }

        // Quote submitted (pending → quoted) → notify customer
        if ($booking->status === 'quoted') {
            if ($customer) {
                $latestQuote = $booking->quotes()->where('status', 'pending')->latest()->first();
                $amount = $latestQuote ? number_format((float) $latestQuote->amount) : '';
                $this->dispatcher->send(
                    user: $customer,
                    type: 'booking_update',
                    title: 'Quote received',
                    body: 'Your technician sent a quote'.($amount ? " of {$amount}" : '').'. Review and accept to proceed.',
                    data: ['booking_id' => $booking->id],
                    notifiable: $booking,
                );
            }

            return;
        }

        // Customer accepted (quoted → accepted) → notify technician
        if ($booking->status === 'accepted' && $previous === 'quoted') {
            if ($technicianUser) {
                $this->dispatcher->send(
                    user: $technicianUser,
                    type: 'booking_update',
                    title: 'Quote accepted',
                    body: 'Your quote was accepted. The customer is ready to proceed.',
                    data: ['booking_id' => $booking->id],
                    notifiable: $booking,
                );
            }

            return;
        }

        // Customer rejected (quoted → pending) → notify technician
        if ($booking->status === 'pending' && $previous === 'quoted') {
            if ($technicianUser) {
                $this->dispatcher->send(
                    user: $technicianUser,
                    type: 'booking_update',
                    title: 'Quote rejected',
                    body: 'Your quote was rejected by the customer. You may submit a revised quote.',
                    data: ['booking_id' => $booking->id],
                    notifiable: $booking,
                );
            }
        }
    }
}
