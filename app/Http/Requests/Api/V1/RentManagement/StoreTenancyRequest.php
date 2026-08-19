<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'unit_id' => ['sometimes', 'nullable', 'integer', 'exists:units,id'],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'tenant_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tenant_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'tenant_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'rent_amount' => ['required', 'integer', 'min:0'],
            'deposit_amount' => ['sometimes', 'integer', 'min:0'],
            'due_day_of_month' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'start_date' => ['required', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after:start_date'],
        ];
    }
}
