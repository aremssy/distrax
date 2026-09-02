<?php

namespace App\Http\Requests\Api\V1\Listing;

use App\Enums\PropertyType;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy gate checked in controller
    }

    public function rules(): array
    {
        return [
            'zone_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'type' => ['sometimes', Rule::in(PropertyType::values())],
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'language_tag' => ['sometimes', 'string', 'max:10'],
            // Numeric upper bounds mirror the property_listings column types so a
            // valid form never overflows the database (bigint / int / smallint / tinyint).
            'price' => ['sometimes', 'integer', 'min:0', 'max:999999999999'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', Rule::in(Currency::activeCached()->pluck('code')->all())],
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

            // Seller intake (Market module) — all optional, seller-declared
            'distress_reason_category' => ['sometimes', 'nullable', Rule::in(['divorce', 'relocation', 'debt', 'estate_probate', 'bank_repossession', 'urgent_cash_need', 'other'])],
            'distress_reason_visibility' => ['sometimes', Rule::in(['public', 'disclosure_only', 'private'])],
            'expected_closing_period' => ['sometimes', 'nullable', Rule::in(['flexible', '30_days', '60_days', '90_days', 'immediate'])],
            'negotiation_flexibility' => ['sometimes', 'nullable', Rule::in(['firm', 'negotiable', 'highly_negotiable', 'make_an_offer'])],
            'expected_market_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'inspection_access_enabled' => ['sometimes', 'boolean'],

            // Seller identity — written to the User, not the listing (see controller)
            'seller_type' => ['sometimes', 'nullable', Rule::in(['individual', 'company', 'estate', 'executor_administrator', 'bank_institution', 'agent', 'developer'])],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'poa_document' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Title / supporting documents — written to property_documents (see controller)
            'title_documents' => ['sometimes', 'nullable', 'array', 'max:10'],
            'title_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
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
