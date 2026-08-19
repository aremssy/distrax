<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'avatar' => $this->whenLoaded('user', fn () => $this->user->avatar ? asset('storage/'.$this->user->avatar) : null),
            'bio' => $this->bio,
            'skills' => $this->skills ?? [],
            'available_time' => $this->available_time ?? [],
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
            'jobs_done' => $this->when(
                isset($this->completed_bookings_count),
                fn () => (int) $this->completed_bookings_count
            ),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (float) $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ])),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
