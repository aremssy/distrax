<?php

namespace App\Http\Requests\Website\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'token.required' => 'This password reset link is invalid. Please request a new one.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'That does not look like a valid email address.',
            'password.required' => 'Please choose a new password.',
            'password.min' => 'Your new password must be at least 4 characters long.',
            'password.confirmed' => 'The two passwords do not match. Please retype them.',
        ];
    }
}
