<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}
