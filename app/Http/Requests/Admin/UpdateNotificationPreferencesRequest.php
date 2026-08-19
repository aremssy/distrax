<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'preferences' => ['required', 'array'],
            'preferences.*.event' => ['required', 'string'],
            'preferences.*.channel' => ['required', Rule::in(['email', 'sms', 'push', 'in_app'])],
            'preferences.*.enabled' => ['required', 'boolean'],
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
            'preferences.required' => 'Please choose at least one notification preference before saving.',
            'preferences.array' => 'Your notification preferences could not be read. Please refresh the page and try again.',
            'preferences.*.event.required' => 'Each preference must be linked to a notification event.',
            'preferences.*.channel.required' => 'Please choose a delivery channel for each preference.',
            'preferences.*.channel.in' => 'That delivery channel is not available. Choose email, SMS, push or in-app.',
            'preferences.*.enabled.required' => 'Please choose whether each notification is switched on or off.',
            'preferences.*.enabled.boolean' => 'Please choose whether each notification is switched on or off.',
        ];
    }
}
