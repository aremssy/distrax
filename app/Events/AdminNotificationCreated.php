<?php

namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever an admin notification is persisted. Implements ShouldBroadcast so
 * the app is real-time ready: enabling a driver (e.g. Reverb) pushes these to the
 * private per-admin channel with zero code changes. Until then delivery stays on the
 * topbar bell's polling loop, and the configured "null"/"log" driver makes this a no-op.
 */
class AdminNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AdminNotification $notification) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.'.$this->notification->admin_id)];
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
            'link' => $this->notification->link,
        ];
    }
}
