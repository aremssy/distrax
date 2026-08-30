<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'session_id', 'query', 'response', 'context', 'latency_ms', 'was_helpful'])]
class AskDistraxQuery extends Model
{
    use HasFactory;
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'was_helpful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
