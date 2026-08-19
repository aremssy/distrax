<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'technician_booking_id' => $this->technician_booking_id,
            'amount' => (int) $this->amount,
            'currency' => setting('default_currency', 'BDT'),
            'description' => $this->description,
            'valid_until' => $this->valid_until?->toIso8601String(),
            'status' => $this->status,
            'technician' => $this->whenLoaded('technician', fn () => [
                'id' => $this->technician->id,
                'name' => $this->technician->user?->name,
                'rating' => (float) $this->technician->rating,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
