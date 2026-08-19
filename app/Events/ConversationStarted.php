<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Conversation $conversation) {}

    /**
     * Notifies the recipient on their personal channel: "user.{id}"
     * Client subscribes as "private-user.{id}".
     *
     * @return Channel|Channel[]
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('user.'.$this->conversation->recipient_id);
    }

    public function broadcastAs(): string
    {
        return 'conversation.started';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'listing_id' => $this->conversation->property_listing_id,
            'starter_id' => $this->conversation->starter_id,
        ];
    }
}
