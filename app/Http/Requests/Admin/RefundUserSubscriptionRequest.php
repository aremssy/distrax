<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RefundUserSubscriptionRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
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
            'amount.required' => 'Please enter the amount to refund.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.integer' => 'The amount must be a whole number, without decimals or currency symbols.',
            'amount.min' => 'The amount must be greater than zero.',
            'reason.required' => 'Please explain why this subscription is being refunded.',
            'reason.string' => 'The reason must be text.',
            'reason.max' => 'The reason cannot be longer than 1000 characters.',
            'admin_note.string' => 'The admin note must be text.',
            'admin_note.max' => 'The admin note cannot be longer than 1000 characters.',
        ];
    }
}
