<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'note' => $this->note,
            'listing' => $this->whenLoaded('listing', fn () => [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'type' => $this->listing->type,
                'address' => $this->listing->address,
            ]),
            'requester' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
