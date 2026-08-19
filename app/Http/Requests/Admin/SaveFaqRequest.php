<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFaqRequest extends FormRequest
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
            'question' => [
                'required',
                'string',
                'max:255',
                Rule::unique('faqs', 'question')
                    ->where(fn ($query) => $query->where('category', $this->input('category')))
                    ->ignore($this->route('faq')),
            ],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
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
            'question.required' => 'Please enter the question visitors will see.',
            'question.max' => 'The question cannot be longer than 255 characters.',
            'question.unique' => 'This question already exists in the selected category.',
            'answer.required' => 'Please write an answer for this question.',
            'answer.max' => 'The answer cannot be longer than 5000 characters.',
            'category.max' => 'The category cannot be longer than 100 characters.',
            'sort_order.integer' => 'The sort order must be a whole number.',
            'sort_order.min' => 'The sort order cannot be less than 0.',
            'is_active.boolean' => 'Please select a valid active status for this FAQ.',
        ];
    }
}
