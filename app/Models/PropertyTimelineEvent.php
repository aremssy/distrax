<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['property_listing_id', 'event_type', 'description', 'privacy_level', 'meta', 'occurred_at'])]
class PropertyTimelineEvent extends Model
{
    use HasFactory;
    public const EVENT_TYPES = [
        'listed', 'price_change', 'verification_completed', 'inspection_booked',
        'offer_made', 'status_change', 'disclosed_change',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
