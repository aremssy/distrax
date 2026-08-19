<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserSubscriptionRequest extends FormRequest
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
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'starts_at' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
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
            'user_id.required' => 'Please choose the user to subscribe.',
            'user_id.integer' => 'Please choose the user from the list.',
            'user_id.exists' => 'That user account no longer exists.',
            'subscription_plan_id.required' => 'Please choose a subscription plan.',
            'subscription_plan_id.integer' => 'Please choose the subscription plan from the list.',
            'subscription_plan_id.exists' => 'That subscription plan no longer exists.',
            'starts_at.date' => 'Please enter a valid start date.',
            'auto_renew.boolean' => 'Please select a valid option for auto renew.',
        ];
    }
}
