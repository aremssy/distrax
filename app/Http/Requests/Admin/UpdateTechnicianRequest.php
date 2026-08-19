<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicianRequest extends FormRequest
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
            'technician_category_id' => ['required', 'integer', 'exists:technician_categories,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:80'],
            'available_time' => ['nullable', 'array'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:80'],
            'hourly_rate' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive', 'suspended'])],
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
            'technician_category_id.required' => 'Please choose a category for this technician.',
            'technician_category_id.integer' => 'Please choose a category from the list.',
            'technician_category_id.exists' => 'That category no longer exists. Please pick another.',
            'zone_id.integer' => 'Please choose a zone from the list.',
            'zone_id.exists' => 'That zone no longer exists. Please pick another.',
            'bio.max' => 'The bio is too long. Please keep it to 1000 characters or fewer.',
            'skills.array' => 'Please add the skills one at a time.',
            'skills.*.string' => 'Each skill must be text.',
            'skills.*.max' => 'Each skill must be 80 characters or fewer.',
            'available_time.array' => 'Please set the availability using the schedule fields.',
            'experience_years.required' => 'Please enter how many years of experience this technician has.',
            'experience_years.integer' => 'Years of experience must be a whole number, like 5.',
            'experience_years.min' => 'Years of experience cannot be negative.',
            'experience_years.max' => 'Years of experience cannot be more than 80.',
            'hourly_rate.integer' => 'The hourly rate must be a whole number, like 250.',
            'hourly_rate.min' => 'The hourly rate cannot be negative.',
            'is_available.boolean' => 'Please mark this technician as either available or unavailable.',
            'is_verified.boolean' => 'Please mark this technician as either verified or unverified.',
            'status.required' => 'Please choose a status for this technician.',
            'status.in' => 'Please choose a valid technician status: pending, active, inactive or suspended.',
        ];
    }
}
