<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['valuation_id', 'property_listing_id', 'external_reference', 'address', 'sale_price', 'sale_date', 'distance_km', 'similarity_score'])]
class ComparableProperty extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
        ];
    }

    public function valuation(): BelongsTo
    {
        return $this->belongsTo(Valuation::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
