<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $countryCode = $this->input('country_code') ?? $this->user()->country_code ?? config('app.default_country');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['sometimes', 'nullable', new CountryWisePhoneNumber($countryCode), Rule::unique('users', 'phone')->ignore($this->user()->id)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'language' => ['sometimes', 'string', 'max:10'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'phone_visibility' => ['sometimes', Rule::in(['everyone', 'registered', 'none'])],
            'buying_for' => ['sometimes', 'nullable', Rule::in(['my_home', 'investment', 'fix_flip', 'development', 'land_banking', 'commercial'])],
            'is_institutional' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.unique' => 'That phone number is already in use.',
            'country_code.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2 format).',
        ];
    }
}
