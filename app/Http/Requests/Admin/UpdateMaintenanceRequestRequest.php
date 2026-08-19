<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequestRequest extends FormRequest
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
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['open', 'assigned', 'in_progress', 'completed', 'closed'])],
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
            'technician_id.integer' => 'Please choose a technician from the list.',
            'technician_id.exists' => 'That technician no longer exists. Please pick another.',
            'priority.required' => 'Please choose a priority for this request.',
            'priority.in' => 'Please choose a valid priority: low, normal, high or urgent.',
            'status.required' => 'Please choose a status for this request.',
            'status.in' => 'Please choose a valid request status: open, assigned, in progress, completed or closed.',
        ];
    }
}
