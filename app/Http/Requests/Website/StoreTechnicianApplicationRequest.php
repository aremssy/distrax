<?php

namespace App\Http\Requests\Website;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The skills field arrives from the form as one comma-separated string.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('skills'))) {
            $this->merge([
                'skills' => collect(explode(',', $this->input('skills')))
                    ->map(fn (string $skill): string => trim($skill))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'technician_category_id' => ['required', 'integer', 'exists:technician_categories,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:100'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'hourly_rate' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'technician_category_id.required' => 'Please choose the type of work you do.',
            'technician_category_id.exists' => 'The service category you selected is no longer available.',
            'zone_id.required' => 'Please choose the area you work in.',
            'zone_id.exists' => 'The area you selected is no longer available.',
            'bio.max' => 'Your bio cannot be longer than 1,000 characters.',
            'skills.max' => 'Please list no more than 20 skills.',
            'skills.*.max' => 'Each skill must be 100 characters or fewer.',
            'experience_years.required' => 'Please tell us how many years of experience you have.',
            'experience_years.min' => 'Years of experience cannot be negative.',
            'experience_years.max' => 'Please enter 60 years of experience or fewer.',
            'hourly_rate.min' => 'Your hourly rate cannot be negative.',
        ];
    }
}
