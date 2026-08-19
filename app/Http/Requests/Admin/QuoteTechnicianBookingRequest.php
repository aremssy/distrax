<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuoteTechnicianBookingRequest extends FormRequest
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
        return [
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'valid_until' => ['nullable', 'date'],
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
            'technician_id.required' => 'Please choose the technician this quote is for.',
            'technician_id.integer' => 'Please choose a technician from the list.',
            'technician_id.exists' => 'That technician no longer exists. Please pick another.',
            'amount.required' => 'Please enter the quoted amount.',
            'amount.integer' => 'The quoted amount must be a whole number, like 1500.',
            'amount.min' => 'The quoted amount must be at least 1.',
            'description.max' => 'The description is too long. Please keep it to 1000 characters or fewer.',
            'valid_until.date' => 'Please enter a valid expiry date for this quote.',
        ];
    }
}
