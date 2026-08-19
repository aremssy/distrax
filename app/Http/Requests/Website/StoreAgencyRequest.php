<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'logo' => ['nullable', File::image()->max('2mb')],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for your agency.',
            'zone_id.exists' => 'The zone you selected no longer exists.',
            'logo.image' => 'The logo must be a JPG, PNG, GIF or WEBP file.',
            'logo.max' => 'The logo must be 2MB or smaller.',
            'website.url' => 'Please enter a valid website address, starting with http:// or https://.',
        ];
    }
}
