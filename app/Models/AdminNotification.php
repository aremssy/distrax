<?php

namespace App\Models;

use Database\Factories\AdminNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['admin_id', 'type', 'title', 'body', 'link', 'is_read', 'notifiable_type', 'notifiable_id'])]
class AdminNotification extends Model
{
    /** @use HasFactory<AdminNotificationFactory> */
    use HasFactory;

    /**
     * Icon + colour styling per notification type. Single source of truth shared
     * by the notifications page and the topbar bell feed.
     *
     * @var array<string, array{icon: string, style: string}>
     */
    public const TYPE_META = [
        'new_user_registered' => ['icon' => 'user-plus', 'style' => 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-400'],
        'listing_reported' => ['icon' => 'flag', 'style' => 'bg-rose-50 text-rose-500 dark:bg-rose-500/15 dark:text-rose-400'],
        'listing_pending_approval' => ['icon' => 'clipboard-document-list', 'style' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400'],
        'payment_received' => ['icon' => 'banknotes', 'style' => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400'],
        'refund_requested' => ['icon' => 'banknotes', 'style' => 'bg-orange-50 text-orange-500 dark:bg-orange-500/15 dark:text-orange-400'],
        'booking_created' => ['icon' => 'calendar-days', 'style' => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400'],
        'technician_application' => ['icon' => 'wrench-screwdriver', 'style' => 'bg-violet-50 text-violet-500 dark:bg-violet-500/15 dark:text-violet-400'],
        'dispute_opened' => ['icon' => 'exclamation-circle', 'style' => 'bg-rose-50 text-rose-500 dark:bg-rose-500/15 dark:text-rose-400'],
        'review_flagged' => ['icon' => 'information-circle', 'style' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400'],
    ];

    public const DEFAULT_META = ['icon' => 'bell', 'style' => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400'];

    /**
     * Styling for a given type, falling back to a neutral default.
     *
     * @return array{icon: string, style: string}
     */
    public static function metaFor(?string $type): array
    {
        return self::TYPE_META[$type] ?? self::DEFAULT_META;
    }

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
