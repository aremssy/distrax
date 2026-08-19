<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
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
            'language_id' => ['required', 'exists:languages,id'],
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
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
            'language_id.required' => 'Please choose the language for this translation.',
            'language_id.exists' => 'That language no longer exists. Please choose another one.',
            'group.required' => 'Please enter a translation group, like messages.',
            'group.max' => 'Keep the translation group under 100 characters.',
            'key.required' => 'Please enter a translation key.',
            'key.max' => 'Keep the translation key under 255 characters.',
            'value.required' => 'Please enter the translated text.',
            'value.string' => 'The translated text must be text.',
        ];
    }
}
