<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['property_listing_id', 'buyer_id', 'amount', 'currency_code', 'terms', 'status', 'expires_at'])]
class Offer extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(Negotiation::class);
    }

    public function deal(): HasOne
    {
        return $this->hasOne(Deal::class);
    }
}
