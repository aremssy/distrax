<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['property_listing_id', 'risk_area', 'level', 'why_explanation', 'evidence_ref_id', 'factors', 'notes', 'assessed_at'])]
class RiskAssessment extends Model
{
    use HasFactory;
    public const AREAS = [
        'title', 'ownership', 'legal', 'occupancy', 'physical_condition',
        'planning', 'liquidity', 'transaction_complexity',
    ];

    protected function casts(): array
    {
        return [
            'factors' => 'array',
            'assessed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
