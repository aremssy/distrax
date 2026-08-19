<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveListingPackageRequest extends FormRequest
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
            'price' => ['required', 'integer', 'min:0'],
            'post_quota' => ['required', 'integer', 'min:1', 'max:65535'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:80'],
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
            'name.required' => 'Please give the package a name.',
            'name.max' => 'The package name cannot be longer than 120 characters.',
            'price.required' => 'Please enter the package price. Use 0 for a free package.',
            'price.integer' => 'The price must be a whole number, without decimals or currency symbols.',
            'price.min' => 'The price cannot be negative.',
            'post_quota.required' => 'Please enter how many posts this package includes.',
            'post_quota.integer' => 'The post quota must be a whole number, like 10.',
            'post_quota.min' => 'The package must include at least 1 post.',
            'post_quota.max' => 'The post quota cannot be greater than 65535.',
            'duration_days.required' => 'Please enter how many days the package lasts, like 30.',
            'duration_days.integer' => 'The duration must be a whole number of days, like 30.',
            'duration_days.min' => 'The package must last at least 1 day.',
            'duration_days.max' => 'The package cannot last longer than 3650 days (10 years).',
            'features.array' => 'Please add the package features as a list.',
            'features.*.string' => 'Each feature must be text.',
            'features.*.max' => 'Each feature must be 80 characters or fewer.',
            'is_active.boolean' => 'Please select a valid option for the active status.',
            'sort_order.integer' => 'The sort order must be a whole number, like 1.',
            'sort_order.min' => 'The sort order cannot be negative.',
        ];
    }

    /**
     * Get the validated data with the package defaults applied.
     *
     * @param  array<int, string>|string|null  $key
     * @param  mixed  $default
     */
    public function validated($key = null, $default = null): mixed
    {
        $package = $this->route('package');

        $data = parent::validated();
        $data['features'] = array_values(array_unique($data['features'] ?? []));
        $data['is_active'] = $this->boolean('is_active', $package?->is_active ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return is_null($key) ? $data : data_get($data, $key, $default);
    }
}
