<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateAreaLandingPageRequest extends FormRequest
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
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'featured_image' => ['nullable', File::image()->max('2mb')],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
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
            'headline.required' => 'Please enter a headline for this landing page.',
            'headline.max' => 'Keep the headline under 255 characters.',
            'subheadline.max' => 'Keep the subheadline under 500 characters.',
            'featured_image.image' => 'The featured image must be a JPG, PNG, GIF or WEBP file.',
            'featured_image.max' => 'The featured image must be 2MB or smaller.',
            'features.array' => 'Features must be a list of items.',
            'features.*.string' => 'Each feature must be text.',
            'features.*.max' => 'Keep each feature under 255 characters.',
            'is_active.boolean' => 'Please choose whether this landing page is active or not.',
        ];
    }
}
