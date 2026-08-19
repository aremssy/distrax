<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkStoreZonesRequest extends FormRequest
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
            'parent_id' => ['required', 'exists:zones,id'],
            'type' => ['required', 'in:country,state,city,area'],
            'names' => ['required', 'string', 'max:10000'],
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
            'parent_id.required' => 'Please choose the parent zone these new zones belong to.',
            'parent_id.exists' => 'The parent zone you selected no longer exists.',
            'type.required' => 'Please choose whether these zones are countries, states, cities or areas.',
            'type.in' => 'The zone type must be country, state, city or area.',
            'names.required' => 'Please enter at least one zone name, one per line.',
            'names.max' => 'The list of names is too long. Please add fewer than 10,000 characters at a time.',
        ];
    }
}
