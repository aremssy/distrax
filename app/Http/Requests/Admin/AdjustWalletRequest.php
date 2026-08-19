<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustWalletRequest extends FormRequest
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
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please choose whether to credit or debit the wallet.',
            'type.in' => 'Please choose a valid adjustment type: credit or debit.',
            'amount.required' => 'Please enter the amount to adjust the wallet by.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.integer' => 'The amount must be a whole number, without decimals or currency symbols.',
            'amount.min' => 'The amount must be greater than zero.',
            'description.max' => 'The description cannot be longer than 255 characters.',
        ];
    }
}
