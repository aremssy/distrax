<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (int) $this->price,
            'currency' => setting('default_currency', 'BDT'),
            'post_quota' => (int) $this->post_quota,
            'duration_days' => (int) $this->duration_days,
            'features' => $this->features ?? [],
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
