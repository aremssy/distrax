<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'technician_id' => ['sometimes', 'nullable', 'integer', 'exists:technicians,id'],
        ];
    }
}
