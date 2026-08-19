<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->native_name,
            'direction' => $this->direction ?? 'ltr',
            'is_default' => (bool) $this->is_default,
        ];
    }
}
