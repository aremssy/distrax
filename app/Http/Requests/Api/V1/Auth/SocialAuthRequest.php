<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(['google', 'facebook'])],
            'token' => ['required', 'string', 'max:2048'],
        ];
    }
}
