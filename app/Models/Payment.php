<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'payable_type', 'payable_id', 'property_listing_id', 'gateway', 'amount', 'currency', 'gateway_fee', 'status', 'transaction_ref', 'gateway_ref', 'gateway_response', 'coupon_id', 'discount_amount', 'paid_at'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The listing a "boost" package purchase applies to, when applicable. */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
