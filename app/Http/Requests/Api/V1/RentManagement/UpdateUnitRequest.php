<?php

namespace App\Http\Requests\Api\V1\RentManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'floor' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rent_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['vacant', 'occupied', 'maintenance'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
