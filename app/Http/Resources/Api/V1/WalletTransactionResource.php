<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
