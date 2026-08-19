<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
