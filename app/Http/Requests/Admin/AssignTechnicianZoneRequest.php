<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianZoneRequest extends FormRequest
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
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
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
            'zone_id.required' => 'Please choose a zone to assign this technician to.',
            'zone_id.integer' => 'Please choose a zone from the list.',
            'zone_id.exists' => 'That zone no longer exists. Please pick another.',
        ];
    }
}
