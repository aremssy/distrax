<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,

            // Dates
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'nights' => (int) $this->check_in->diffInDays($this->check_out),
            'guests' => $this->guests,

            // Amount
            'amount' => (int) $this->amount,
            'currency' => setting('default_currency', 'BDT'),
            'refunded_amount' => (int) $this->refunded_amount,

            // Cancellation
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'refund_info' => $this->when(
                isset($this->refund_info),
                fn () => $this->refund_info
            ),

            // Payment stub — will be enriched in Phase 10
            'payment_status' => $this->when(
                $this->relationLoaded('payments'),
                fn () => $this->payments->first()?->status ?? 'unpaid'
            ),

            // Relations
            'listing' => $this->whenLoaded('listing', fn () => [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'type' => $this->listing->type,
                'address' => $this->listing->address,
            ]),
            'guest' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
