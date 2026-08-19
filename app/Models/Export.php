<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single export request and its lifecycle. Small exports are processed synchronously
 * and land here already "completed"; large ones are queued and this row tracks their
 * progress. Either way it doubles as the per-admin download history.
 */
#[Fillable([
    'user_id', 'resource', 'format', 'scope', 'columns', 'filters', 'ids',
    'status', 'total_rows', 'processed_rows', 'file_path', 'file_name',
    'error', 'download_count', 'completed_at', 'expires_at',
])]
class Export extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'filters' => 'array',
            'ids' => 'array',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'download_count' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Progress as a 0-100 integer for the client progress bar.
     */
    public function progressPercent(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        if (! $this->total_rows) {
            return $this->status === self::STATUS_QUEUED ? 0 : 5;
        }

        return (int) min(99, round(($this->processed_rows / $this->total_rows) * 100));
    }
}
