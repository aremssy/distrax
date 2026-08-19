<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'resolved_at' => $this->resolved_at?->toIso8601String(),

            'listing' => $this->whenLoaded('listing', fn () => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'address' => $this->listing->address,
            ] : null),

            'technician' => $this->whenLoaded('technician', fn () => $this->technician ? [
                'id' => $this->technician->id,
                'name' => $this->technician->user?->name,
                'rating' => (float) $this->technician->rating,
            ] : null),

            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ]),

            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
