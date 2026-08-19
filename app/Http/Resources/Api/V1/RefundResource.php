<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'amount' => (int) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
