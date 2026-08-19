<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'description' => $this->description,
            'address' => $this->address,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'is_urgent' => $this->is_urgent,
            'agreed_amount' => $this->agreed_amount,
            'currency' => setting('default_currency', 'BDT'),

            'technician' => $this->whenLoaded('technician', fn () => [
                'id' => $this->technician->id,
                'name' => $this->technician->user?->name,
                'rating' => (float) $this->technician->rating,
                'category' => $this->technician->category?->name,
            ]),

            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'quotes' => $this->whenLoaded('quotes', fn () => QuoteResource::collection($this->quotes)),

            'payment_status' => $this->when(
                $this->relationLoaded('payments'),
                fn () => $this->payments->first()?->status ?? 'unpaid'
            ),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
