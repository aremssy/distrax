<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_type' => $this->property_type,
            'label' => $this->label,
            'key' => $this->key,
            'type' => $this->type,
            'options' => $this->hasOptions() ? ($this->options ?? []) : null,
            'is_required' => (bool) $this->is_required,
            'is_filterable' => (bool) $this->is_filterable,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
