<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_listing_id', 'user_id', 'check_in', 'check_out', 'guests', 'amount', 'currency_code', 'status', 'special_requests', 'cancelled_at', 'refunded_amount'])]
class HotelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        // Snapshot the listing's currency onto the booking so the charged amount and
        // its currency stay consistent even if the listing is later re-priced.
        static::creating(function (HotelBooking $booking): void {
            if (empty($booking->currency_code) && $booking->property_listing_id) {
                $booking->currency_code = PropertyListing::whereKey($booking->property_listing_id)->value('currency_code');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
