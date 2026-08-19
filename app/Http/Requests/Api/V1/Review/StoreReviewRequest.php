<?php

namespace App\Http\Requests\Api\V1\Review;

use App\Models\PropertyListing;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * Short alias → fully-qualified Eloquent class.
     * Mirrors the pattern used in StoreReportRequest.
     *
     * @var array<string, class-string>
     */
    public const REVIEWABLE_TYPES = [
        'listing' => PropertyListing::class,
        'technician' => Technician::class,
        'user' => User::class,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewable_type' => ['required', Rule::in(array_keys(self::REVIEWABLE_TYPES))],
            'reviewable_id' => ['required', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
