<?php

namespace App\Http\Requests\Admin;

use App\Models\PlanFeature;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSubscriptionPlanRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'post_limit' => ['required', 'integer', 'min:0', 'max:65535'],
            'unlocked_features' => ['nullable', 'array'],
            'unlocked_features.*' => ['string', Rule::in(PlanFeature::pluck('key'))],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'name.required' => 'Please give the plan a name.',
            'name.max' => 'The plan name cannot be longer than 120 characters.',
            'price.required' => 'Please enter the plan price. Use 0 for a free plan.',
            'price.integer' => 'The price must be a whole number, without decimals or currency symbols.',
            'price.min' => 'The price cannot be negative.',
            'duration_days.required' => 'Please enter how many days the plan lasts, like 30.',
            'duration_days.integer' => 'The duration must be a whole number of days, like 30.',
            'duration_days.min' => 'The plan must last at least 1 day.',
            'duration_days.max' => 'The plan cannot last longer than 3650 days (10 years).',
            'post_limit.required' => 'Please enter how many posts this plan allows. Use 0 for none.',
            'post_limit.integer' => 'The post limit must be a whole number.',
            'post_limit.min' => 'The post limit cannot be negative.',
            'post_limit.max' => 'The post limit cannot be greater than 65535.',
            'unlocked_features.array' => 'Please select the unlocked features from the list.',
            'unlocked_features.*.in' => 'One of the selected features is not available. Please choose from the list.',
            'is_featured.boolean' => 'Please select a valid option for the featured flag.',
            'is_active.boolean' => 'Please select a valid option for the active status.',
            'sort_order.integer' => 'The sort order must be a whole number, like 1.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }

    /**
     * Get the validated data with the plan defaults applied.
     *
     * @param  array<int, string>|string|null  $key
     * @param  mixed  $default
     */
    public function validated($key = null, $default = null): mixed
    {
        $plan = $this->route('plan');

        $data = parent::validated();
        $data['unlocked_features'] = array_values(array_unique($data['unlocked_features'] ?? []));
        $data['is_featured'] = $this->boolean('is_featured');
        $data['is_active'] = $this->boolean('is_active', $plan?->is_active ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return is_null($key) ? $data : data_get($data, $key, $default);
    }
}
