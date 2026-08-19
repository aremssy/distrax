<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CountryWisePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Accept either email or phone
        if ($this->filled('phone')) {
            $countryCode = $this->input('country_code', config('app.default_country'));

            return [
                'phone' => ['required', 'string', new CountryWisePhoneNumber($countryCode)],
                'country_code' => ['sometimes', 'string', 'size:2'],
            ];
        }

        return [
            // `filter` (PHP FILTER_VALIDATE_EMAIL) rejects malformed addresses
            // like "test@test"/"foo@bar" that the loose `email` rule lets through,
            // without the network dependency (or example.* rejection) of `dns`.
            // `exists` requires the email to belong to a real account so the user
            // is told up-front when it isn't registered (trades enumeration
            // protection for clearer UX, per product decision).
            'email' => ['required', 'email:filter', 'max:255', 'exists:users,email'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.exists' => 'This email is not registered.',
        ];
    }
}
