<?php

namespace App\Http\Requests\Admin;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateProfileRequest extends FormRequest
{
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
        $countryCode = $this->input('country_code') ?? $this->user()->country_code ?? config('app.default_country');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['nullable', new CountryWisePhoneNumber($countryCode), Rule::unique('users', 'phone')->ignore($this->user()->id)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'avatar' => ['nullable', File::image()->max('2mb')],
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
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address, like name@example.com.',
            'email.unique' => 'That email address is already in use.',
            'phone.unique' => 'That phone number is already in use.',
            'country_code.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2 format).',
            'avatar.image' => 'The avatar must be a JPG, PNG, GIF or WEBP file.',
            'avatar.max' => 'The avatar must be 2MB or smaller.',
        ];
    }
}
