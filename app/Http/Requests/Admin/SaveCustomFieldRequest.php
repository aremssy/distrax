<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCustomFieldRequest extends FormRequest
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
            // 'all' is a valid pseudo-type meaning the field applies to every property type.
            'property_type' => ['required', 'in:'.implode(',', [...PropertyType::values(), 'all'])],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:text,number,select,multiselect,checkbox,radio,date,textarea'],
            'options' => ['nullable', 'string'],
            'is_required' => ['nullable'],
            'is_filterable' => ['nullable'],
            'is_active' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'property_type.required' => 'Please choose which property type this field belongs to.',
            'property_type.in' => 'Please choose a valid property type from the list.',
            'label.required' => 'Please enter a label for this field.',
            'label.max' => 'The label cannot be longer than 100 characters.',
            'type.required' => 'Please choose an input type for this field.',
            'type.in' => 'Please choose a valid input type from the list.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }
}
