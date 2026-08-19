<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            // Email-based reset
            'email' => ['required_without:phone', 'nullable', 'email:rfc,dns', 'max:255'],
            'token' => ['required_without:phone', 'nullable', 'string'],

            // Phone-based reset
            'phone' => ['required_without:email', 'nullable', new CountryWisePhoneNumber($countryCode)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'otp' => ['required_without:email', 'nullable', 'string', 'digits:6'],

            // Common
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
