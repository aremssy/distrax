<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLedgerTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'occurred_on' => ['required', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'property_listing_id' => ['sometimes', 'nullable', 'integer', 'exists:property_listings,id'],
            'tenancy_id' => ['sometimes', 'nullable', 'integer', 'exists:tenancies,id'],
        ];
    }
}
