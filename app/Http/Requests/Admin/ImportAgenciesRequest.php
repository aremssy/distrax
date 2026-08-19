<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportAgenciesRequest extends FormRequest
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
            'csv_file' => ['required', File::types(['csv', 'txt'])->max('2mb')],
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
            'csv_file.required' => 'Please choose a file to import your agencies from.',
            'csv_file.mimes' => 'The import file must be a .csv or .txt file.',
            'csv_file.extensions' => 'The import file must be a .csv or .txt file.',
            'csv_file.max' => 'The import file must be 2MB or smaller.',
            'csv_file.uploaded' => 'The import file could not be uploaded. Please try again with a smaller file.',
        ];
    }
}
