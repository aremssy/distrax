<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SaveBannerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'image' => [$this->route('banner') ? 'nullable' : 'required', File::image()->max('2mb')],
            'link' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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
            'name.required' => 'Please enter a name for this banner.',
            'name.max' => 'The banner name cannot be longer than 120 characters.',
            'image.required' => 'Please add an image for this banner.',
            'image.image' => 'The image must be a JPG, PNG, GIF or WEBP file.',
            'image.max' => 'The image must be 2MB or smaller.',
            'link.max' => 'The link cannot be longer than 255 characters.',
            'position.required' => 'Please choose where this banner should appear.',
            'position.max' => 'The position cannot be longer than 80 characters.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be less than 0.',
            'is_active.boolean' => 'Please select a valid active status for this banner.',
            'starts_at.date' => 'Please enter a valid start date.',
            'ends_at.date' => 'Please enter a valid end date.',
            'ends_at.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
