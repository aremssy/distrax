<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Fillable(['parent_id', 'name', 'slug', 'type', 'lat', 'lng', 'boundary', 'is_active', 'sort_order'])]
class Zone extends Model
{
    use HasFactory;

    /** Shared (non-session) cache key holding the website location-picker options. */
    public const OPTIONS_CACHE_KEY = 'zones.dropdown_options';

    protected function casts(): array
    {
        return [
            'boundary' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /** Recursive eager-loading relationship for tree rendering. */
    public function allChildren(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with('allChildren');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(PropertyListing::class, 'zone_id');
    }

    public function landingPage(): HasOne
    {
        return $this->hasOne(AreaLandingPage::class, 'zone_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Collect all descendant IDs.
     *
     * Walks a single cached parent → children map rather than querying per node,
     * which previously issued one query for every descendant.
     *
     * @return array<int>
     */
    public function descendantIds(): array
    {
        $childMap = self::childMap();
        $ids = [];
        $queue = $childMap[$this->id] ?? [];

        while ($queue !== []) {
            $id = array_pop($queue);
            $ids[] = $id;

            foreach ($childMap[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * Parent id => child ids, for the whole (near-static) zone tree.
     *
     * @return array<int, array<int>>
     */
    public static function childMap(): array
    {
        return Cache::remember('zones.child_map', now()->addHour(), function (): array {
            $map = [];

            foreach (self::query()->select(['id', 'parent_id'])->cursor() as $zone) {
                if ($zone->parent_id !== null) {
                    $map[$zone->parent_id][] = $zone->id;
                }
            }

            return $map;
        });
    }

    /**
     * Active city/area zones for the website location picker.
     *
     * Shared across every visitor — the key holds no session state. The zone a
     * visitor has *selected* is resolved separately by id via {@see self::activeById()}.
     */
    public static function dropdownOptions(): EloquentCollection
    {
        $rows = Cache::remember(self::OPTIONS_CACHE_KEY, now()->addHour(), fn (): array => self::query()
            ->select(['id', 'name', 'type'])
            ->active()
            ->whereIn('type', ['city', 'area'])
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(fn (Zone $zone): array => $zone->getAttributes())
            ->all());

        return self::hydrate($rows);
    }

    /**
     * A single active zone, cached under its own id.
     *
     * The cache key is derived from the zone id (never from the session/user), so two
     * visitors with different selected zones read different keys.
     */
    public static function activeById(int $id): ?self
    {
        $row = Cache::remember(
            self::activeCacheKey($id),
            now()->addHour(),
            fn (): array => self::query()->select(['id', 'name', 'type'])->active()->find($id)?->getAttributes() ?? [],
        );

        return $row === [] ? null : self::hydrate([$row])->first();
    }

    /**
     * Drop the cached tree, the picker options and this zone's own lookup entry
     * whenever the zone hierarchy changes.
     */
    public static function flushChildMap(): void
    {
        Cache::forget('zones.child_map');
        Cache::forget(self::OPTIONS_CACHE_KEY);
    }

    public static function activeCacheKey(int $id): string
    {
        return "zones.active.{$id}";
    }

    /**
     * Find the nearest active zone of a given type using the Haversine formula.
     * Returns null when no zones with coordinates exist for the type.
     */
    public static function nearest(float $lat, float $lng, string $type = 'area'): ?self
    {
        return self::query()
            ->selectRaw(
                '*, ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lng) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ) AS distance',
                [$lat, $lng, $lat]
            )
            ->where('type', $type)
            ->where('is_active', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('distance')
            ->first();
    }

    // ── Booted ───────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Zone $zone): void {
            if (empty($zone->slug)) {
                $zone->slug = self::uniqueSlug(Str::slug($zone->name));
            }
        });

        static::updating(function (Zone $zone): void {
            if ($zone->isDirty('name') && ! $zone->isDirty('slug')) {
                $zone->slug = self::uniqueSlug(Str::slug($zone->name), $zone->id);
            }
        });

        static::saved(function (Zone $zone): void {
            self::flushChildMap();
            Cache::forget(self::activeCacheKey($zone->id));
        });

        static::deleted(function (Zone $zone): void {
            self::flushChildMap();
            Cache::forget(self::activeCacheKey($zone->id));
        });
    }

    private static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 1;

        while (self::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
