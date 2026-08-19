<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $eventType,
        public readonly string $title,
        public readonly string $body,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->body)
            ->line('You are receiving this because you have email notifications enabled. Manage preferences in your account settings.');
    }
}
