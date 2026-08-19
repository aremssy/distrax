<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogCategoryRequest extends FormRequest
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
        $blogCategory = $this->route('blogCategory');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('blog_categories', 'name')->ignore($blogCategory)],
            'description' => ['nullable', 'string', 'max:500'],
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
            'name.max' => 'The category name cannot be longer than 120 characters.',
            'name.unique' => 'Another blog category already uses that name. Try a different one.',
            'description.max' => 'Keep the description to 500 characters or fewer.',
            'is_active.boolean' => 'Please select a valid active status for this category.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be less than 0.',
        ];
    }
}
