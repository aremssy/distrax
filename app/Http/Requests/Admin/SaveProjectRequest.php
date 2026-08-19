<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SaveProjectRequest extends FormRequest
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
        $project = $this->route('project');

        return [
            'project_category_id' => ['nullable', 'integer', 'exists:project_categories,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'developer_name' => ['nullable', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($project)],
            'description' => ['nullable', 'string', 'max:3000'],
            'price_from' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['upcoming', 'ongoing', 'completed'])],
            'cover_image' => ['nullable', File::image()->max('2mb')],
            'completion_date' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
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
            'title.required' => 'Please enter a title for this project.',
            'title.max' => 'The title cannot be longer than 200 characters.',
            'slug.unique' => 'That slug is already used by another project. Try a different one.',
            'status.required' => 'Please choose a project status.',
            'status.in' => 'Please choose upcoming, ongoing or completed.',
            'project_category_id.exists' => 'The selected project category no longer exists.',
            'zone_id.exists' => 'The selected zone no longer exists. Please pick another.',
            'price_from.integer' => 'The starting price must be a whole number, without decimals or commas.',
            'price_from.min' => 'The starting price cannot be negative.',
            'description.max' => 'Keep the description under 3000 characters.',
            'completion_date.date' => 'Please enter a valid completion date.',
            'cover_image.image' => 'The cover image must be a JPG, PNG, GIF or WEBP file.',
            'cover_image.max' => 'The cover image must be 2MB or smaller.',
        ];
    }
}
