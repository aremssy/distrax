<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTechnicianCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('technician_categories', 'name')->ignore($category)],
            'icon' => ['nullable', 'string', 'max:80'],
            'commission_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the custom validation messages for this request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for this category.',
            'name.max' => 'The category name must be 120 characters or fewer.',
            'name.unique' => 'A category with this name already exists. Please choose a different name.',
            'icon.max' => 'The icon name must be 80 characters or fewer.',
            'commission_rate.required' => 'Please enter a commission rate for this category.',
            'commission_rate.integer' => 'The commission rate must be a whole number, like 10.',
            'commission_rate.min' => 'The commission rate cannot be negative.',
            'commission_rate.max' => 'The commission rate cannot be more than 100.',
            'is_active.boolean' => 'Please mark this category as either active or inactive.',
            'sort_order.integer' => 'The sort order must be a whole number, like 3.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }
}
