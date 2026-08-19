<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'avatar' => $this->whenLoaded('user', fn () => $this->user->avatar ? asset('storage/'.$this->user->avatar) : null),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ]),
            'zone' => $this->whenLoaded('zone', fn () => $this->zone ? [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
            ] : null),
            'rating' => (float) $this->rating,
            'hourly_rate' => $this->hourly_rate,
            'experience_years' => $this->experience_years,
            'is_available' => $this->is_available,
            'is_verified' => $this->is_verified,
            'status' => $this->status,
        ];
    }
}
