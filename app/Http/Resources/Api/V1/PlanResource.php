<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (int) $this->price,
            'currency' => setting('default_currency', 'BDT'),
            'duration_days' => (int) $this->duration_days,
            'post_limit' => $this->post_limit === 0 ? null : (int) $this->post_limit,
            'post_limit_label' => $this->post_limit === 0 ? 'Unlimited' : (string) $this->post_limit,
            'unlocked_features' => $this->unlocked_features ?? [],
            'is_featured' => $this->is_featured,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
