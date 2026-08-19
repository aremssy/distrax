<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundPaymentRequest extends FormRequest
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
            'status' => ['required', Rule::in(['approved', 'rejected', 'processed'])],
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
            'status.required' => 'Please choose what to do with this refund.',
            'status.in' => 'Please choose a valid refund status: approved, rejected or processed.',
            'admin_note.string' => 'The admin note must be text.',
            'admin_note.max' => 'The admin note cannot be longer than 1000 characters.',
        ];
    }
}
