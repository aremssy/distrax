<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SaveTestimonialRequest extends FormRequest
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
            'role' => ['nullable', 'string', 'max:120'],
            'quote' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('testimonials', 'quote')
                    ->where(fn ($query) => $query->where('name', $this->input('name')))
                    ->ignore($this->route('testimonial')),
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'avatar' => ['nullable', File::image()->max('2mb')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
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
            'name.required' => 'Please enter the name of the person giving this testimonial.',
            'name.max' => 'The name cannot be longer than 120 characters.',
            'role.max' => 'The role cannot be longer than 120 characters.',
            'quote.required' => 'Please enter the testimonial quote.',
            'quote.max' => 'Keep the quote to 1000 characters or fewer.',
            'quote.unique' => 'This testimonial has already been added for this person.',
            'rating.required' => 'Please give a rating between 1 and 5 stars.',
            'rating.integer' => 'The rating must be a whole number of stars.',
            'rating.min' => 'Please give a rating of at least 1 star.',
            'rating.max' => 'Please give a rating of no more than 5 stars.',
            'avatar.image' => 'The avatar must be a JPG, PNG, GIF or WEBP file.',
            'avatar.max' => 'The avatar must be 2MB or smaller.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be less than 0.',
            'is_featured.boolean' => 'Please select a valid featured option for this testimonial.',
            'is_active.boolean' => 'Please select a valid active status for this testimonial.',
        ];
    }
}
