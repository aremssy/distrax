<?php

namespace App\Http\Requests\Api\V1\Listing;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy gate checked in controller
    }

    public function rules(): array
    {
        return [
            'images' => ['sometimes', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
            'cover_index' => ['sometimes', 'integer', 'min:0'],
            'videos' => ['sometimes', 'array', 'max:5'],
            'videos.*.url' => ['required_with:videos', 'string', 'url', 'max:500'],
            'videos.*.source' => ['sometimes', 'nullable', Rule::in(['youtube', 'vimeo', 'direct'])],
            'videos.*.thumbnail' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->hasFile('images') && ! $this->filled('videos')) {
                $v->errors()->add('media', 'At least one image or video must be provided.');
            }
        });
    }
}
