<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterVerifyRequest extends FormRequest
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
            'phone' => ['required', 'string', new CountryWisePhoneNumber($countryCode)],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'otp' => ['required', 'string', 'digits:6'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['sometimes', 'nullable', 'string', Password::defaults()],
        ];
    }
}
