<?php

namespace App\Http\Requests\Api\V1\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TopupWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:100'],
            'gateway' => ['required', 'string', 'max:50'],
        ];
    }
}
