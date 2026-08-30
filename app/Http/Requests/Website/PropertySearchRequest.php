<?php

namespace App\Http\Requests\Website;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:200'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'type' => ['nullable', Rule::in(PropertyType::values())],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'allowed_for' => ['nullable', Rule::in(['bachelor', 'family', 'both'])],
            'furnished' => ['nullable', 'boolean'],
            'parking' => ['nullable', 'boolean'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:200'],
            'advance_months' => ['nullable', 'integer', 'min:0', 'max:60'],
            'featured' => ['nullable', 'boolean'],
            'verified' => ['nullable', 'boolean'],
            'verification_status' => ['nullable', Rule::in(['distrax_verified', 'disclosure_required', 'in_progress', 'under_legal_review', 'not_verified'])],
            'deal_tag' => ['nullable', Rule::in(['urgent_sale', 'below_market_value', 'bank_institutional_asset', 'estate_sale', 'owner_distress', 'price_reduced', 'renovation_opportunity', 'development_opportunity'])],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc', 'rating', 'distance', 'deal_score'])],
            'near_lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_if:sort,distance'],
            'near_lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_if:sort,distance'],
            'sw_lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:sw_lng,ne_lat,ne_lng'],
            'sw_lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:sw_lat,ne_lat,ne_lng'],
            'ne_lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:sw_lat,sw_lng,ne_lng'],
            'ne_lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:sw_lat,sw_lng,ne_lat'],
            'polygon' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['min_price', 'max_price']) && $this->integer('max_price') < $this->integer('min_price')) {
                    $validator->errors()->add('max_price', 'The maximum price must be greater than or equal to the minimum price.');
                }
            },
        ];
    }
}
