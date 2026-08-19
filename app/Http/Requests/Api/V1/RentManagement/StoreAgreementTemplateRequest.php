<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
