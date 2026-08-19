<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'slug', 'description', 'is_active', 'sort_order'])]
class BlogCategory extends Model
{
    use HasFactory;

    /** Shared (non-session) cache key holding the website navigation categories. */
    public const NAV_CACHE_KEY = 'blog_categories.nav';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'blog_category_id');
    }

    // ── Caching ──────────────────────────────────────────────────────────────

    /**
     * The active categories shown in the website navigation on every page.
     *
     * Cached as raw attribute rows — the database cache store has
     * serializable_classes=false and refuses serialized objects — then rehydrated.
     */
    public static function navigationCached(): EloquentCollection
    {
        $rows = Cache::remember(self::NAV_CACHE_KEY, now()->addHour(), fn (): array => self::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (BlogCategory $category): array => $category->getAttributes())
            ->all());

        return self::hydrate($rows);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::NAV_CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
