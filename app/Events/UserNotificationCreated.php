<?php

namespace App\Events;

use App\Models\UserNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a website-user notification is persisted. Broadcasts on the private
 * `user.{id}` channel already authorised in routes/channels.php, keeping the app
 * real-time ready while the dashboard bell continues to poll.
 */
class UserNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserNotification $notification) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
        ];
    }
}
