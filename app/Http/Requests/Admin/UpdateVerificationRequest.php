<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationRequest extends FormRequest
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
            'status' => ['required', Rule::in(['verified', 'rejected', 'pending'])],
            'note' => ['nullable', 'string', 'max:1000'],
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
            'status.required' => 'Please choose a verification status before saving.',
            'status.in' => 'Please choose a valid verification status: verified, rejected or pending.',
            'note.max' => 'Keep the note under 1000 characters.',
        ];
    }
}
