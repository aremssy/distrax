<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateReviewRequest extends FormRequest
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
            'action' => ['required', Rule::in(['verify', 'hide', 'show', 'delete'])],
            'note' => ['nullable', 'string', 'max:1000'],
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
            'action.required' => 'Please choose a moderation action for this review.',
            'action.in' => 'That moderation action is not available. Choose verify, hide, show or delete.',
            'note.max' => 'Keep the moderation note under 1000 characters.',
        ];
    }
}
