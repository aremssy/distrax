<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'gateway' => ['required', 'string', 'max:80'],
            'account_ref' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
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
            'user_id.required' => 'Please choose the user to pay out.',
            'user_id.integer' => 'Please choose the user from the list.',
            'user_id.exists' => 'That user account no longer exists.',
            'amount.required' => 'Please enter the payout amount.',
            'amount.integer' => 'The amount must be a whole number, without decimals or currency symbols.',
            'amount.min' => 'The amount must be greater than zero.',
            'currency.max' => 'The currency code cannot be longer than 10 characters.',
            'gateway.required' => 'Please choose the payment gateway to send the payout through.',
            'gateway.max' => 'The payment gateway name cannot be longer than 80 characters.',
            'account_ref.max' => 'The account reference cannot be longer than 255 characters.',
            'description.max' => 'The description cannot be longer than 500 characters.',
        ];
    }
}
