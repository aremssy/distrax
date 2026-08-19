<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'check_in' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
