<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['property_listing_id', 'score', 'breakdown', 'computed_at'])]
class DealScore extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
