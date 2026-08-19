<?php

namespace App\Http\Requests\Api\V1\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
