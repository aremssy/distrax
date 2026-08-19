<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => [
                'required',
                'integer',
                Rule::exists('technicians', 'id')->where('status', 'active')->where('is_verified', true)->where('is_available', true),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'address' => ['required', 'string', 'max:500'],
            'is_urgent' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.exists' => 'The selected technician is not available for booking.',
        ];
    }
}
