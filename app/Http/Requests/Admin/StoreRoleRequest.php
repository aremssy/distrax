<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An admin may only grant a new role permissions they themselves hold, so
     * they cannot mint a role more powerful than their own account.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->user()->coversPermissionIds($this->input('permissions', []))) {
                $validator->errors()->add('permissions', 'You can only grant permissions you already hold.');
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
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
            'name.required' => 'Please enter an internal name for this role.',
            'name.unique' => 'A role with this name already exists. Please choose a different name.',
            'name.max' => 'Keep the role name under 255 characters.',
            'display_name.required' => 'Please enter a display name for this role.',
            'display_name.max' => 'Keep the display name under 255 characters.',
            'guard_name.max' => 'Keep the guard name under 255 characters.',
            'permissions.array' => 'Please choose permissions from the list.',
            'permissions.*.exists' => 'One of the selected permissions no longer exists. Please refresh the page and try again.',
        ];
    }
}
