<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateCmsPageRequest extends FormRequest
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
        $cmsPage = $this->route('cmsPage');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($cmsPage)],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'featured_image' => ['nullable', File::image()->max('2mb')],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
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
            'title.required' => 'Please enter a title for this page.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'slug.unique' => 'That slug is already used by another page. Try a different one.',
            'slug.max' => 'The slug cannot be longer than 255 characters.',
            'content.string' => 'The page content must be text.',
            'meta_title.max' => 'Keep the meta title to 120 characters or fewer.',
            'meta_description.max' => 'Keep the meta description to 320 characters or fewer.',
            'featured_image.image' => 'The featured image must be a JPG, PNG, GIF or WEBP file.',
            'featured_image.max' => 'The featured image must be 2MB or smaller.',
            'status.required' => 'Please choose a status for this page.',
            'status.in' => 'Choose a valid status: draft, published or archived.',
        ];
    }
}
