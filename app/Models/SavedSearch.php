<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'criteria', 'alert_on', 'is_mandate', 'min_discount_pct', 'min_deal_score', 'frequency'])]
class SavedSearch extends Model
{
    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'alert_on' => 'boolean',
            'is_mandate' => 'boolean',
            'last_alerted_at' => 'datetime',
        ];
    }

    /** Deal Radar rules are saved searches with is_mandate=true — same criteria shape, no separate table. */
    public function scopeDealRadar(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('is_mandate', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
