<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id ?? auth()->id();

        $otherParty = $this->starter_id === $userId ? $this->recipient : $this->starter;

        return [
            'id' => $this->id,
            'listing' => $this->whenLoaded('listing', fn () => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'type' => $this->listing->type,
                'status' => $this->listing->status,
            ] : null),
            'other_party' => $otherParty ? [
                'id' => $otherParty->id,
                'name' => $otherParty->name,
                'avatar' => $otherParty->avatar ? asset('storage/'.$otherParty->avatar) : null,
            ] : null,
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage
                ? [
                    'body' => $this->latestMessage->body,
                    'is_mine' => $this->latestMessage->sender_id === $userId,
                    'created_at' => $this->latestMessage->created_at->toIso8601String(),
                ]
                : null),
            'unread_count' => $this->when(
                isset($this->unread_count),
                fn () => (int) $this->unread_count
            ),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
