<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reviewer_id', 'reviewable_type', 'reviewable_id', 'rating', 'body', 'is_verified', 'is_visible', 'moderated_by', 'moderation_note', 'moderated_at', 'owner_reply'])]
class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_visible' => 'boolean',
            'moderated_at' => 'datetime',
            'owner_replied_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
