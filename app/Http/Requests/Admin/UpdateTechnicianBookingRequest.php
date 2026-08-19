<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicianBookingRequest extends FormRequest
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
            'status' => ['required', Rule::in(['pending', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled'])],
            'agreed_amount' => ['nullable', 'integer', 'min:0'],
            'is_urgent' => ['nullable', 'boolean'],
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
            'status.required' => 'Please choose a status for this booking.',
            'status.in' => 'Please choose a valid booking status.',
            'agreed_amount.integer' => 'The agreed amount must be a whole number, like 1500.',
            'agreed_amount.min' => 'The agreed amount cannot be negative.',
            'is_urgent.boolean' => 'Please mark this booking as either urgent or not urgent.',
        ];
    }
}
