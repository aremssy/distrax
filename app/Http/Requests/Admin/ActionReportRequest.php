<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActionReportRequest extends FormRequest
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
            'action' => ['required', Rule::in(['warn', 'block', 'remove', 'dismiss'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
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
            'action.required' => 'Please choose an action to take on this report.',
            'action.in' => 'That moderation action is not available. Choose warn, block, remove or dismiss.',
            'admin_note.max' => 'Keep the admin note under 1000 characters.',
        ];
    }
}
