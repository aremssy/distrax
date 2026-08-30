<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_listing_id', 'offer_id', 'buyer_id', 'seller_id', 'agreed_amount', 'currency_code', 'stage', 'closed_at'])]
class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function legalMatters(): HasMany
    {
        return $this->hasMany(LegalMatter::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(PropertyDocument::class, 'documentable');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
