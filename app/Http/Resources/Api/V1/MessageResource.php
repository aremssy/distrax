<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id ?? auth()->id();

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'body' => $this->body,
            'is_phone_revealed' => $this->is_phone_revealed,
            'is_mine' => $this->sender_id === $userId,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar ? asset('storage/'.$this->sender->avatar) : null,
            ]),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
