<?php

namespace App\Http\Requests\Website\Auth;

use App\Models\User;
use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $countryCode = $this->input('country_code', config('app.default_country'));

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', new CountryWisePhoneNumber($countryCode)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'password' => ['required', Password::min(4)],
            'terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'country_code.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2 format).',
        ];
    }
}
