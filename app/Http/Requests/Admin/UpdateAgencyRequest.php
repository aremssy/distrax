<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateAgencyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', File::image()->max('2mb')],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive,suspended'],
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
            'name.required' => 'Please enter a name for this agency.',
            'name.max' => 'The agency name cannot be longer than 255 characters.',
            'logo.image' => 'The logo must be a JPG, PNG, GIF or WEBP file.',
            'logo.max' => 'The logo must be 2MB or smaller.',
            'phone.max' => 'The phone number cannot be longer than 20 characters.',
            'email.email' => 'Please enter a valid contact email, like info@example.com.',
            'website.url' => 'Please enter a valid website address, starting with http:// or https://.',
            'address.max' => 'The address cannot be longer than 255 characters.',
            'description.max' => 'The description cannot be longer than 5,000 characters.',
            'is_verified.boolean' => 'Please tick or untick the verified box to set the agency verification.',
            'status.required' => 'Please choose a status for this agency.',
            'status.in' => 'The agency status must be active, inactive or suspended.',
        ];
    }
}
