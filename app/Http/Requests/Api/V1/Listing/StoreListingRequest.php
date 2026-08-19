<?php

namespace App\Http\Requests\Api\V1\Listing;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy gate checked in controller
    }

    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', Rule::in(PropertyType::values())],
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'language_tag' => ['sometimes', 'string', 'max:10'],
            // Numeric upper bounds mirror the property_listings column types so a
            // valid form never overflows the database (bigint / int / smallint / tinyint).
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'service_charge' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:999999999999'],
            'advance_months' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:24'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'bedrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'bathrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'floor' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
            'total_floors' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'area_sqft' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'parking' => ['sometimes', 'boolean'],
            'furnished' => ['sometimes', 'boolean'],
            'allowed_for' => ['sometimes', 'nullable', Rule::in(['bachelor', 'family', 'both'])],
            'utility_flags' => ['sometimes', 'nullable', 'array'],
            'utility_flags.*' => ['string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'custom_fields' => ['sometimes', 'nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'images' => ['sometimes', 'nullable', 'array', 'max:'.(int) setting('listing_max_images', 10)],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'floor_plans' => ['sometimes', 'nullable', 'array', 'max:5'],
            'floor_plans.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bedrooms.max' => 'Bedrooms cannot be more than 255.',
            'bathrooms.max' => 'Bathrooms cannot be more than 255.',
            'floor.max' => 'Floor cannot be more than 65,535.',
            'total_floors.max' => 'Total floors cannot be more than 65,535.',
            'area_sqft.max' => 'Area is too large. Please enter a realistic value.',
            'advance_months.max' => 'Advance months cannot be more than 24.',
            'price.max' => 'That price is too large. Please enter a realistic amount.',
            'service_charge.max' => 'That service charge is too large. Please enter a realistic amount.',
            'price.integer' => 'The price must be a whole number, without commas or symbols.',
        ];
    }
}
