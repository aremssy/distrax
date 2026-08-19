<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
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
            'user_email' => ['required', 'email', 'exists:users,email'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
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
            'user_email.required' => 'Please enter the email address of the user you want to add as an agent.',
            'user_email.email' => 'Please enter a valid user email, like name@example.com.',
            'user_email.exists' => 'No registered user was found with that email address.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'bio.max' => 'The bio cannot be longer than 2,000 characters.',
            'phone.max' => 'The phone number cannot be longer than 20 characters.',
            'whatsapp.max' => 'The WhatsApp number cannot be longer than 20 characters.',
        ];
    }
}
