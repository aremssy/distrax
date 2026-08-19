<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterStartRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'country_code.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2 format).',
        ];
    }
}
