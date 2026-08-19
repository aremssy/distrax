<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,

            // Parent summary — present only when the relation is loaded
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
                'type' => $this->parent->type,
            ]),

            // Children count — uses withCount() when available, falls back to relation
            'children_count' => $this->when(
                isset($this->children_count),
                fn () => (int) $this->children_count,
                fn () => $this->relationLoaded('children') ? $this->children->count() : null,
            ),

            // Nested children — only when explicitly loaded (tree mode)
            'children' => $this->whenLoaded(
                'children',
                fn () => self::collection($this->children)
            ),

            // Distance from a resolve/nearby query — present only when the
            // model has a 'distance' attribute appended via selectRaw
            'distance_km' => $this->when(
                isset($this->distance),
                fn () => round((float) $this->distance, 2)
            ),
        ];
    }
}
