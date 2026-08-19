<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenancy_id' => ['required', 'integer', 'exists:tenancies,id'],
            'agreement_template_id' => ['sometimes', 'nullable', 'integer', 'exists:agreement_templates,id'],
        ];
    }
}
