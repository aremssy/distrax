<?php

namespace App\Http\Requests\Api\V1\Visit;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
