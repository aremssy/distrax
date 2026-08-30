<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'verification_case_id', 'property_listing_id', 'reference_id', 'score', 'seller_verification_status',
    'title_status', 'ownership_status', 'survey_status', 'physical_inspection_status', 'legal_review_status',
    'planning_status', 'disclosure_count', 'breakdown', 'verification_date', 'expiry_review_date', 'qr_code_url',
])]
class VerificationScore extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'verification_date' => 'datetime',
            'expiry_review_date' => 'date',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(VerificationCase::class, 'verification_case_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
