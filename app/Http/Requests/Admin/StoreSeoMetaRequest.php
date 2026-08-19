<?php

namespace App\Http\Requests\Admin;

use App\Models\SeoMeta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreSeoMetaRequest extends FormRequest
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
            'seoable_type' => ['required', 'string', Rule::in(array_keys(SeoMeta::SEOABLE_TYPES))],
            'seoable_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $type = $this->input('seoable_type');

                    // Only check existence once we know the type is a valid model;
                    // an invalid type is already reported by its own rule.
                    if (array_key_exists($type, SeoMeta::SEOABLE_TYPES)
                        && ! $type::whereKey($value)->exists()) {
                        $fail('The selected item no longer exists. Please choose another one.');
                    }
                },
            ],
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
            'seoable_type.required' => 'Please choose what this SEO meta belongs to.',
            'seoable_type.in' => 'Please choose a valid content type: page, blog, property listing or zone.',
            'seoable_id.required' => 'Please choose the item this SEO meta belongs to.',
            'seoable_id.integer' => 'Please pick the item from the list instead of typing it in.',
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
