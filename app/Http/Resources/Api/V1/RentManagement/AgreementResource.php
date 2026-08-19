<?php

namespace App\Http\Resources\Api\V1\RentManagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgreementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenancy_id' => $this->tenancy_id,
            'agreement_template_id' => $this->agreement_template_id,
            'content' => $this->content,
            'status' => $this->status,
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
