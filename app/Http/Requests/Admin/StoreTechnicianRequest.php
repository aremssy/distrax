<?php

namespace App\Http\Requests\Admin;

use App\Models\Technician;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The skills field arrives from the form as one comma-separated string.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('skills'))) {
            $this->merge([
                'skills' => collect(explode(',', $this->input('skills')))
                    ->map(fn (string $skill): string => trim($skill))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_email' => [
                'required', 'email', 'exists:users,email',
                // A user can only hold one technician profile.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $userId = User::query()->where('email', $value)->value('id');

                    if ($userId && Technician::query()->where('user_id', $userId)->exists()) {
                        $fail('That user already has a technician profile.');
                    }
                },
            ],
            'technician_category_id' => ['required', 'integer', 'exists:technician_categories,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:80'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:80'],
            'hourly_rate' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive', 'suspended'])],
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
            'user_email.required' => 'Please enter the email address of the user who will own this technician profile.',
            'user_email.email' => 'Please enter a valid email, like name@example.com.',
            'user_email.exists' => 'No registered user was found with that email address.',
            'technician_category_id.required' => 'Please choose a service category.',
            'technician_category_id.exists' => 'The service category you selected no longer exists.',
            'zone_id.exists' => 'The zone you selected no longer exists.',
            'bio.max' => 'The bio cannot be longer than 1,000 characters.',
            'skills.max' => 'Please list no more than 20 skills.',
            'skills.*.max' => 'Each skill must be 80 characters or fewer.',
            'experience_years.required' => 'Please enter the number of years of experience.',
            'experience_years.max' => 'Please enter 80 years of experience or fewer.',
            'hourly_rate.min' => 'The hourly rate cannot be negative.',
            'status.required' => 'Please choose a status.',
        ];
    }
}
