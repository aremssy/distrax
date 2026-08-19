<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['user_id', 'subscription_plan_id', 'starts_at', 'ends_at', 'status', 'posts_used', 'auto_renew', 'cancelled_at', 'refunded_at', 'refunded_amount'])]
class UserSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at->lte(now())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
