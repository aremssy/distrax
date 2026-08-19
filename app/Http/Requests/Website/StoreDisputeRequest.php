<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['property_listing_id' => ['required', 'integer', 'exists:property_listings,id'], 'subject' => ['required', 'string', 'max:200'], 'description' => ['required', 'string', 'max:3000']];
    }
}
