<?php

namespace App\Http\Requests\Admin;

use App\Models\Campaign;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCampaignRequest extends FormRequest
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
            'channel' => ['required', Rule::in(['email', 'push', 'sms'])],
            'email_template_id' => ['nullable', 'required_if:channel,email', 'integer', 'exists:email_templates,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'target_segments' => ['nullable', 'array'],
            'target_segments.*' => ['string', Rule::in(array_keys(Campaign::SEGMENTS))],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'sent', 'cancelled'])],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
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
            'name.required' => 'Please enter a name for this campaign.',
            'name.max' => 'Keep the campaign name under 120 characters.',
            'channel.required' => 'Please choose how this campaign will be sent.',
            'channel.in' => 'Please choose a valid channel: email, push or SMS.',
            'email_template_id.required_if' => 'Please choose an email template for an email campaign.',
            'email_template_id.integer' => 'Please pick the email template from the list instead of typing it in.',
            'email_template_id.exists' => 'That email template no longer exists. Please choose another one.',
            'subject.max' => 'Keep the subject under 255 characters.',
            'target_segments.array' => 'Target segments must be a list of audience segments.',
            'target_segments.*.in' => 'One of the chosen audience segments is not valid. Please pick from the list.',
            'status.required' => 'Please choose a status for this campaign.',
            'status.in' => 'Please choose a valid status: draft, scheduled, sent or cancelled.',
            'scheduled_at.date' => 'Please enter a valid date and time for the schedule.',
            'scheduled_at.after_or_equal' => 'The scheduled time must be in the future.',
        ];
    }
}
