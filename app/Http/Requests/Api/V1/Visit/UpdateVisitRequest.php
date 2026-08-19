<?php

namespace App\Http\Requests\Api\V1\Visit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['confirmed', 'cancelled'])],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
