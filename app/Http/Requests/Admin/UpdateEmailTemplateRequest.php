<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
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
            'subject.max' => 'Keep the subject under 255 characters.',
            'body.required' => 'Please enter the template body.',
            'body.string' => 'The template body must be text.',
        ];
    }
}
