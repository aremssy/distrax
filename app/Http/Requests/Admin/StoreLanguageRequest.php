<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'direction' => ['required', 'in:ltr,rtl'],
            'is_active' => ['nullable'],
            'is_default' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'code.required' => 'Please enter a language code, like en or bn.',
            'code.max' => 'Use a language code of 10 characters or fewer, like en or bn.',
            'code.unique' => 'That language code is already in use.',
            'name.required' => 'Please enter the language name in English, like Bengali.',
            'name.max' => 'Keep the language name under 100 characters.',
            'native_name.required' => 'Please enter the native name of the language, like বাংলা.',
            'native_name.max' => 'Keep the native name under 100 characters.',
            'direction.required' => 'Please choose the text direction for this language.',
            'direction.in' => 'Please choose a valid text direction: ltr or rtl.',
            'sort_order.integer' => 'The sort order must be a whole number, like 1.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }
}
