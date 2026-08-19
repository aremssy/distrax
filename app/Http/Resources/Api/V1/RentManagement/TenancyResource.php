<?php

namespace App\Http\Resources\Api\V1\RentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_listing_id' => $this->property_listing_id,
            'unit_id' => $this->unit_id,
            'tenant' => [
                'id' => $this->tenant_id,
                'name' => $this->tenantName(),
                'phone' => $this->tenant_phone,
                'email' => $this->tenant_email,
            ],
            'rent_amount' => $this->rent_amount,
            'deposit_amount' => $this->deposit_amount,
            'due_day_of_month' => $this->due_day_of_month,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
