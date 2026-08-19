<?php

namespace App\Http\Requests\Website;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianQuoteRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
            'valid_until' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the amount you are quoting.',
            'amount.min' => 'The quoted amount must be greater than zero.',
            'description.max' => 'The quote description cannot be longer than 2,000 characters.',
            'valid_until.after' => 'The expiry date must be in the future.',
        ];
    }
}
