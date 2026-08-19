<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'slug', 'technician_category_id', 'zone_id', 'bio', 'skills', 'available_time', 'experience_years', 'hourly_rate', 'is_available', 'is_verified', 'status'])]
class Technician extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'available_time' => 'array',
            'is_available' => 'boolean',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Technician $technician): void {
            if (empty($technician->slug)) {
                $name = User::whereKey($technician->user_id)->value('name');
                $technician->slug = self::uniqueSlug(Str::slug((string) $name) ?: 'technician');
            }
        });
    }

    private static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 1;

        while (self::withTrashed()->where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TechnicianCategory::class, 'technician_category_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TechnicianBooking::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function scopeAvailableForZone(Builder $query, Zone $zone): Builder
    {
        return $query->where('status', 'active')
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where(function (Builder $q) use ($zone): void {
                $q->where('zone_id', $zone->id)
                    ->orWhereHas('zone', fn (Builder $zoneQuery) => $zoneQuery->where('parent_id', $zone->id));
            });
    }
}
