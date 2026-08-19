<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'amount' => (int) $this->amount,
            'discount_amount' => (int) $this->discount_amount,
            'currency' => $this->currency,
            'payable_type' => class_basename($this->payable_type),
            'payable_id' => $this->payable_id,
            'gateway_ref' => $this->gateway_ref,
            'transaction_ref' => $this->transaction_ref,
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
        ];
    }
}
