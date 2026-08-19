<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExtendUserSubscriptionRequest extends FormRequest
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
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
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
            'days.required' => 'Please enter the number of days to extend by, like 30.',
            'days.integer' => 'Enter the number of days to extend by, like 30.',
            'days.min' => 'The subscription must be extended by at least 1 day.',
            'days.max' => 'The subscription cannot be extended by more than 3650 days (10 years).',
        ];
    }
}
