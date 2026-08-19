<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateSeoMetaRequest extends FormRequest
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
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', File::image()->max('2mb')],
            'additional_meta' => ['nullable', 'array'],
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
            'meta_title.max' => 'Keep the meta title under 120 characters for best SEO results.',
            'meta_description.max' => 'Keep the meta description under 320 characters for best SEO results.',
            'og_title.max' => 'Keep the Open Graph title under 120 characters.',
            'og_description.max' => 'Keep the Open Graph description under 320 characters.',
            'og_image.image' => 'The OG image must be a JPG, PNG, GIF or WEBP file.',
            'og_image.max' => 'The OG image must be 2MB or smaller.',
            'additional_meta.array' => 'Additional meta must be a list of tag and value pairs.',
        ];
    }
}
