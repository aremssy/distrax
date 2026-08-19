<?php

namespace App\Http\Requests\Admin;

use App\Models\HotelBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RefundHotelBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var HotelBooking $booking */
        $booking = $this->route('booking');

        return [
            'amount' => ['required', 'integer', 'min:1', 'max:'.$booking->amount],
        ];
    }

    /**
     * Get the custom validation messages for this request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the amount you want to refund.',
            'amount.integer' => 'The refund amount must be a whole number, like 2500.',
            'amount.min' => 'The refund amount must be at least 1.',
            'amount.max' => 'The refund cannot be more than the amount paid.',
        ];
    }
}
