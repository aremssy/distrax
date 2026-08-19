<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportAgencyListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', File::types(['csv', 'txt'])->max('2mb')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please choose a file to import your listings from.',
            'csv_file.mimes' => 'The import file must be a .csv or .txt file.',
            'csv_file.extensions' => 'The import file must be a .csv or .txt file.',
            'csv_file.max' => 'The import file must be 2MB or smaller.',
        ];
    }
}
