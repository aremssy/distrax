<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_category_id' => ['required', 'integer', 'exists:technician_categories,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'skills' => ['sometimes', 'nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'available_time' => ['sometimes', 'nullable', 'array'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'hourly_rate' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
