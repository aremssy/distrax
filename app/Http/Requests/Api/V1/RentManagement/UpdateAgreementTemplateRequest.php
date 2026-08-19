<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgreementTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:20000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
