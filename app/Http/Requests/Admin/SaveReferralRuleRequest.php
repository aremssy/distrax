<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReferralRuleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'reward_type' => ['required', Rule::in(['credit', 'discount_percent', 'discount_fixed', 'free_listing', 'featured_listing'])],
            'reward_value' => ['required', 'integer', 'min:1'],
            'reward_description' => ['nullable', 'string', 'max:500'],
            'max_referrals' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'min_purchase_amount' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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
            'name.required' => 'Please enter a name for this referral rule.',
            'name.max' => 'Keep the rule name under 120 characters.',
            'reward_type.required' => 'Please choose the type of reward this rule gives.',
            'reward_type.in' => 'Please choose a valid reward type: credit, percentage discount, fixed discount, free listing or featured listing.',
            'reward_value.required' => 'Please enter the reward value.',
            'reward_value.integer' => 'The reward value must be a whole number, like 100.',
            'reward_value.min' => 'The reward value must be at least 1.',
            'reward_description.max' => 'Keep the reward description under 500 characters.',
            'max_referrals.integer' => 'The maximum referrals must be a whole number, like 10.',
            'max_referrals.min' => 'Allow at least 1 referral, or leave this blank for no limit.',
            'max_referrals.max' => 'The maximum referrals cannot be higher than 65535.',
            'min_purchase_amount.integer' => 'The minimum purchase amount must be a whole number, like 500.',
            'min_purchase_amount.min' => 'The minimum purchase amount cannot be negative.',
            'is_active.boolean' => 'Please choose whether this rule is active or not.',
            'starts_at.date' => 'Please enter a valid start date.',
            'expires_at.date' => 'Please enter a valid expiry date.',
            'expires_at.after_or_equal' => 'The expiry date must be on or after the start date.',
        ];
    }
}
