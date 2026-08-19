<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /** Supported events and channels — kept in sync with the DB enum values. */
    public const EVENTS = ['chat', 'alert', 'booking', 'lead'];

    public const CHANNELS = ['email', 'sms', 'push', 'in_app'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [];

        foreach (self::EVENTS as $event) {
            $rules[$event] = ['sometimes', 'array'];
            foreach (self::CHANNELS as $channel) {
                $rules["{$event}.{$channel}"] = ['sometimes', 'boolean'];
            }
        }

        return $rules;
    }
}
