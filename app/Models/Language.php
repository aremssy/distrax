<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['code', 'name', 'native_name', 'direction', 'is_active', 'is_default', 'sort_order'])]
class Language extends Model
{
    use HasFactory;

    /** Shared (non-session) cache key holding every active language. */
    public const CACHE_KEY = 'languages.active';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    // ── Caching ──────────────────────────────────────────────────────────────

    /**
     * Every active language, display-ordered. Rendered on every website page.
     *
     * Cached as raw attribute rows — the database cache store has
     * serializable_classes=false and refuses serialized objects — then rehydrated.
     */
    public static function activeCached(): EloquentCollection
    {
        $rows = Cache::remember(self::CACHE_KEY, now()->addHours(6), fn (): array => self::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Language $language): array => $language->getAttributes())
            ->all());

        return self::hydrate($rows);
    }

    /** The active language for a code, resolved from the shared cached list. */
    public static function findActiveByCode(?string $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::activeCached()->firstWhere('code', $code);
    }

    /** The active default language, resolved from the shared cached list. */
    public static function defaultActive(): ?self
    {
        return self::activeCached()->firstWhere('is_default', true);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
