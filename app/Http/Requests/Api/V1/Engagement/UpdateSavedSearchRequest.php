<?php

namespace App\Http\Requests\Api\V1\Engagement;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'alert_on' => ['sometimes', 'boolean'],

            'criteria' => ['sometimes', 'array'],
            'criteria.zone_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'criteria.include_children' => ['sometimes', 'boolean'],
            'criteria.type' => ['sometimes', Rule::in(PropertyType::values())],
            'criteria.min_price' => ['sometimes', 'numeric', 'min:0'],
            'criteria.max_price' => ['sometimes', 'numeric', 'min:0'],
            'criteria.bedrooms' => ['sometimes', 'integer', 'min:0'],
            'criteria.bathrooms' => ['sometimes', 'integer', 'min:0'],
            'criteria.allowed_for' => ['sometimes', Rule::in(['bachelor', 'family', 'both'])],
            'criteria.furnished' => ['sometimes', 'boolean'],
            'criteria.parking' => ['sometimes', 'boolean'],
            'criteria.advance_months' => ['sometimes', 'integer', 'min:0', 'max:24'],
            'criteria.keyword' => ['sometimes', 'string', 'max:200'],
        ];
    }
}
