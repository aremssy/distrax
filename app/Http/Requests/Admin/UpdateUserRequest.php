<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * A non-super-admin may never modify a super admin (block, demote, etc.).
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? $this->user()->canManageUser($target)
            : true;
    }

    /**
     * Reject assignment of a role the actor is not permitted to grant — this is
     * the privilege-escalation guard for the role dropdown.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roleId = $this->input('role_id');

            if (! $roleId) {
                return;
            }

            $role = Role::find($roleId);

            if ($role && ! $this->user()->canAssignRole($role)) {
                $validator->errors()->add('role_id', 'You are not allowed to assign this role.');
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', File::image()->max('2mb')],
            'role_id' => ['required', 'exists:roles,id'],
            'is_blocked' => ['nullable', 'boolean'],
            'verification_status' => ['required', 'in:unverified,pending,verified,rejected'],
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
            'name.required' => 'Please enter a name for this user.',
            'name.max' => 'Keep the name under 255 characters.',
            'email.required' => 'Please enter an email address for this user.',
            'email.email' => 'Please enter a valid email address, like name@example.com.',
            'email.unique' => 'Another user is already registered with this email address.',
            'email.max' => 'Keep the email address under 255 characters.',
            'phone.max' => 'Keep the phone number under 20 characters.',
            'avatar.image' => 'The avatar must be a JPG, PNG, GIF or WEBP file.',
            'avatar.max' => 'The avatar must be 2MB or smaller.',
            'role_id.required' => 'Please choose a role for this user.',
            'role_id.exists' => 'That role no longer exists. Please choose another one.',
            'is_blocked.boolean' => 'Please choose whether this user is blocked or not.',
            'verification_status.required' => 'Please choose a verification status for this user.',
            'verification_status.in' => 'Please choose a valid verification status: unverified, pending, verified or rejected.',
        ];
    }
}
