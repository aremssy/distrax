<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['property_listing_id', 'requested_by', 'method', 'estimated_value', 'currency_code', 'confidence_score', 'valued_at', 'metadata'])]
class Valuation extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'valued_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function comparables(): HasMany
    {
        return $this->hasMany(ComparableProperty::class);
    }
}
