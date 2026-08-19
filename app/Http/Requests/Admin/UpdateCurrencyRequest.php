<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
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
        $currency = $this->route('currency');

        return [
            'code' => ['required', 'string', 'max:10', "unique:currencies,code,{$currency->id}"],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'symbol_position' => ['required', 'in:before,after'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:8'],
            'thousands_separator' => ['required', 'string', 'max:5'],
            'decimal_separator' => ['required', 'string', 'max:5'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
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
            'code.required' => 'Please enter a currency code, like BDT or USD.',
            'code.max' => 'Use a currency code of 10 characters or fewer, like BDT or USD.',
            'code.unique' => 'That currency code is already in use.',
            'name.required' => 'Please enter the currency name, like Bangladeshi Taka.',
            'name.max' => 'Keep the currency name under 100 characters.',
            'symbol.required' => 'Please enter the currency symbol, like ৳ or $.',
            'symbol.max' => 'Keep the currency symbol under 10 characters.',
            'symbol_position.required' => 'Please choose where the symbol appears.',
            'symbol_position.in' => 'Please choose a valid symbol position: before or after the amount.',
            'decimal_places.required' => 'Please enter how many decimal places to show.',
            'decimal_places.integer' => 'The decimal places must be a whole number, like 2.',
            'decimal_places.min' => 'The decimal places cannot be negative.',
            'decimal_places.max' => 'Use no more than 8 decimal places.',
            'thousands_separator.required' => 'Please enter a thousands separator, like a comma.',
            'thousands_separator.max' => 'Keep the thousands separator under 5 characters.',
            'decimal_separator.required' => 'Please enter a decimal separator, like a full stop.',
            'decimal_separator.max' => 'Keep the decimal separator under 5 characters.',
            'exchange_rate.required' => 'Please enter the exchange rate.',
            'exchange_rate.numeric' => 'The exchange rate must be a number, like 1.00.',
            'exchange_rate.min' => 'The exchange rate must be greater than zero.',
            'sort_order.integer' => 'The sort order must be a whole number, like 1.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }
}
