<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date', 'date_format:Y-m-d'],
            'to' => ['required', 'date', 'date_format:Y-m-d', 'after:from'],
            'is_available' => ['sometimes', 'boolean'],
            'override_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
