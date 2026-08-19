<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rent_amount' => ['sometimes', 'integer', 'min:0'],
            'deposit_amount' => ['sometimes', 'integer', 'min:0'],
            'due_day_of_month' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['active', 'ended', 'terminated'])],
        ];
    }
}
