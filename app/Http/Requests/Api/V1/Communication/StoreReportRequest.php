<?php

namespace App\Http\Requests\Api\V1\Communication;

use App\Models\PropertyListing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /** @var array<string, class-string> */
    public const REPORTABLE_TYPES = [
        'user' => User::class,
        'listing' => PropertyListing::class,
        'review' => Review::class,
    ];

    public const REASONS = [
        'spam',
        'inappropriate_content',
        'fake_listing',
        'scam',
        'wrong_information',
        'harassment',
        'other',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validType = $this->input('reportable_type');
        $morphClass = self::REPORTABLE_TYPES[$validType] ?? null;

        return [
            'reportable_type' => ['required', Rule::in(array_keys(self::REPORTABLE_TYPES))],
            'reportable_id' => [
                'required',
                'integer',
                $morphClass ? Rule::exists((new $morphClass)->getTable(), 'id') : 'exists:users,id',
            ],
            'reason' => ['required', Rule::in(self::REASONS)],
            'details' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
