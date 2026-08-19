<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['preferences' => ['nullable', 'array'], 'preferences.*.*' => ['nullable', Rule::in(['1', 1, true])]];
    }
}
