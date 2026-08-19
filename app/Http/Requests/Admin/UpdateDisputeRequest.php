<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisputeRequest extends FormRequest
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
            'status' => ['required', Rule::in(['open', 'in_review', 'resolved', 'closed'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'outcome' => ['nullable', Rule::in(['none', 'refund_approved', 'refund_rejected', 'content_removed', 'warning_issued'])],
            'refund_amount' => ['nullable', 'integer', 'min:1'],
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
            'status.required' => 'Please choose the dispute status.',
            'status.in' => 'Please choose a valid dispute status: open, in review, resolved or closed.',
            'assigned_to.integer' => 'Please choose the assignee from the list.',
            'assigned_to.exists' => 'That user account no longer exists.',
            'resolution.string' => 'The resolution must be text.',
            'resolution.max' => 'The resolution cannot be longer than 2000 characters.',
            'outcome.in' => 'Please choose a valid outcome from the list.',
            'refund_amount.integer' => 'The refund amount must be a whole number, without decimals or currency symbols.',
            'refund_amount.min' => 'The refund amount must be greater than zero.',
        ];
    }
}
