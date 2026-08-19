<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Phone-OTP login: {phone, otp?}
        // Email-password login: {email, password}
        if ($this->filled('email')) {
            return [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string'],
            ];
        }

        $countryCode = $this->input('country_code', config('app.default_country'));

        return [
            'phone' => ['required', 'string', new CountryWisePhoneNumber($countryCode)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'otp' => ['sometimes', 'nullable', 'string', 'digits:6'],
        ];
    }
}
