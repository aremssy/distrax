<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCouponRequest extends FormRequest
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
        $coupon = $this->route('coupon');

        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('coupons', 'code')->ignore($coupon)],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'integer', 'min:1', Rule::when(
                $this->input('type') === 'percentage',
                ['max:100'],
            )],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'max_uses_per_user' => ['required', 'integer', 'min:1', 'max:255'],
            'applicable_for' => ['nullable', Rule::in(['subscription', 'listing_package'])],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
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
            'code.required' => 'Please enter a coupon code.',
            'code.unique' => 'That coupon code is already in use. Please choose another.',
            'code.max' => 'The coupon code cannot be longer than 60 characters.',
            'type.required' => 'Please choose whether the discount is a percentage or a fixed amount.',
            'type.in' => 'Please choose a valid discount type: percentage or fixed.',
            'value.required' => 'Please enter the discount value.',
            'value.numeric' => 'The discount value must be a number.',
            'value.integer' => 'The discount value must be a whole number.',
            'value.min' => 'The discount value must be greater than zero.',
            'value.max' => 'A percentage discount cannot be greater than 100%.',
            'max_discount.integer' => 'The maximum discount must be a whole number.',
            'max_discount.min' => 'The maximum discount cannot be negative.',
            'min_order.integer' => 'The minimum order must be a whole number.',
            'min_order.min' => 'The minimum order cannot be negative.',
            'max_uses.integer' => 'The maximum uses must be a whole number, like 100.',
            'max_uses.min' => 'The coupon must allow at least 1 use.',
            'max_uses.max' => 'The maximum uses cannot be greater than 65535.',
            'max_uses_per_user.required' => 'Please enter how many times a single user can use this coupon.',
            'max_uses_per_user.integer' => 'The maximum uses per user must be a whole number, like 1.',
            'max_uses_per_user.min' => 'Each user must be allowed at least 1 use.',
            'max_uses_per_user.max' => 'The maximum uses per user cannot be greater than 255.',
            'applicable_for.in' => 'Please choose what the coupon applies to: subscription or listing package.',
            'starts_at.date' => 'Please enter a valid start date.',
            'expires_at.date' => 'Please enter a valid expiry date.',
            'expires_at.after_or_equal' => 'The expiry date must be on or after the start date.',
            'is_active.boolean' => 'Please select a valid option for the active status.',
        ];
    }

    /**
     * Get the validated data with the coupon defaults applied.
     *
     * @param  array<int, string>|string|null  $key
     * @param  mixed  $default
     */
    public function validated($key = null, $default = null): mixed
    {
        $coupon = $this->route('coupon');

        $data = parent::validated();
        $data['code'] = mb_strtoupper($data['code']);
        $data['min_order'] = $data['min_order'] ?? 0;
        $data['is_active'] = $this->boolean('is_active', $coupon?->is_active ?? true);

        return is_null($key) ? $data : data_get($data, $key, $default);
    }
}
