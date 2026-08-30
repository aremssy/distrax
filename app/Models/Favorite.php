<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'property_listing_id', 'is_watchlist', 'target_price', 'alert_threshold', 'notes'])]
class Favorite extends Model
{
    protected function casts(): array
    {
        return [
            'is_watchlist' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }
}
