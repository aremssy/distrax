<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'price', 'duration_days', 'post_limit', 'unlocked_features', 'is_featured', 'is_active', 'sort_order'])]
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unlocked_features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
