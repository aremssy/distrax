<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkTrashListingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer', 'exists:property_listings,id'],
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one listing to move to trash.',
            'ids.array' => 'The listing selection is invalid. Please select the listings again.',
            'ids.max' => 'You can only trash up to 100 listings at a time.',
            'ids.*.integer' => 'The listing selection is invalid. Please select the listings again.',
            'ids.*.exists' => 'One of the selected listings no longer exists. Please refresh and try again.',
        ];
    }
}
