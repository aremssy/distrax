<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'body' => $this->body,
            'is_verified' => $this->is_verified,
            'owner_reply' => $this->owner_reply,
            'owner_replied_at' => $this->owner_replied_at?->toISOString(),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'avatar' => $this->reviewer->avatar ?? null,
            ]),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
