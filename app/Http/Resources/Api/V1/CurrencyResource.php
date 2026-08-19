<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'symbol_position' => $this->symbol_position ?? 'before',
            'decimal_places' => (int) ($this->decimal_places ?? 2),
            'thousands_separator' => $this->thousands_separator ?? ',',
            'decimal_separator' => $this->decimal_separator ?? '.',
            'exchange_rate' => (float) $this->exchange_rate,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
