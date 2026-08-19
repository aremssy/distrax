<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
