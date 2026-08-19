<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SaveProjectCategoryRequest extends FormRequest
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
        $projectCategory = $this->route('projectCategory');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('project_categories', 'name')->ignore($projectCategory)],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', File::image()->max('2mb')],
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
            'name.unique' => 'A category with this name already exists. Try a different one.',
            'name.max' => 'The name cannot be longer than 120 characters.',
            'description.max' => 'Keep the description under 500 characters.',
            'image.image' => 'The image must be a JPG, PNG, GIF or WEBP file.',
            'image.max' => 'The image must be 2MB or smaller.',
            'sort_order.integer' => 'The sort order must be a whole number.',
        ];
    }
}
