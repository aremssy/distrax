<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:country,state,city,area'],
            'parent_id' => ['nullable', 'exists:zones,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'boundary' => ['nullable', 'json'],
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
            'name.required' => 'Please enter a name for this zone.',
            'name.max' => 'The zone name cannot be longer than 255 characters.',
            'type.required' => 'Please choose whether this zone is a country, state, city or area.',
            'type.in' => 'The zone type must be country, state, city or area.',
            'parent_id.exists' => 'The parent zone you selected no longer exists.',
            'lat.numeric' => 'The latitude must be a number, for example 23.8103.',
            'lat.between' => 'The latitude must be between -90 and 90.',
            'lng.numeric' => 'The longitude must be a number, for example 90.4125.',
            'lng.between' => 'The longitude must be between -180 and 180.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }
}
