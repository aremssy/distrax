<?php

namespace App\Http\Resources\Api\V1\RentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenancy_id' => $this->tenancy_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'amount' => $this->amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? new RentReceiptResource($this->receipt) : null),
        ];
    }
}
