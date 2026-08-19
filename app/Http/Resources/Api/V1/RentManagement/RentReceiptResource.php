<?php

namespace App\Http\Resources\Api\V1\RentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rent_payment_id' => $this->rent_payment_id,
            'tenancy_id' => $this->tenancy_id,
            'receipt_number' => $this->receipt_number,
            'amount' => $this->amount,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
