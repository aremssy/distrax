<?php

namespace App\Http\Resources\Api\V1\RentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_listing_id' => $this->property_listing_id,
            'tenancy_id' => $this->tenancy_id,
            'type' => $this->type,
            'category' => $this->category,
            'amount' => $this->amount,
            'occurred_on' => $this->occurred_on?->toDateString(),
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
