<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['assigned', 'in_progress', 'completed', 'closed'])],
            'technician_id' => ['required_if:status,assigned', 'nullable', 'integer', 'exists:technicians,id'],
        ];
    }
}
