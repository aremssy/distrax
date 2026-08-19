<?php

namespace App\Listeners;

use App\Events\VerificationResulted;
use App\Services\NotificationDispatcher;

class SendVerificationNotification
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    public function handle(VerificationResulted $event): void
    {
        $user = $event->user;

        if ($event->status === 'verified') {
            $this->dispatcher->send(
                user: $user,
                type: 'verification_result',
                title: 'Identity verified',
                body: 'Your identity document has been approved. Your account is now verified.',
                data: ['status' => 'verified'],
            );

            return;
        }

        if ($event->status === 'rejected') {
            $note = $event->note ? " Reason: {$event->note}" : '';
            $this->dispatcher->send(
                user: $user,
                type: 'verification_result',
                title: 'Verification failed',
                body: "Your identity document was not approved.{$note}",
                data: ['status' => 'rejected', 'note' => $event->note],
            );
        }
    }
}
