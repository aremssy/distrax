<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'criteria', 'alert_on'])]
class SavedSearch extends Model
{
    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'alert_on' => 'boolean',
            'last_alerted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
