<?php

namespace App\Support;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\PropertyListing;
use App\Models\Review;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

class HomeRepository
{
    /**
     * TTL (seconds) for the homepage aggregation caches. These feed the public
     * landing page on every anonymous hit; short staleness is acceptable and
     * spares MySQL the repeated COUNT/MIN-MAX/DISTINCT/whereHas scans.
     */
    private const CACHE_TTL = 600;

    /** @return SupportCollection<int, string> */
    public function activePropertyTypes(): SupportCollection
    {
        // The cache store is configured with serializable_classes => false, so only
        // plain arrays/scalars survive a round-trip — cache the array, not the Collection.
        $types = Cache::remember('home.active_property_types', self::CACHE_TTL, fn () => PropertyListing::query()
            ->where('status', 'active')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all());

        return collect($types);
    }

    /** @return Collection<int, Zone> */
    public function activeZones(string $type): Collection
    {
        // Called twice per request (cities + areas); cache the plain rows and
        // rehydrate, same as popularZones().
        $rows = Cache::remember("home.active_zones.{$type}", self::CACHE_TTL, fn () => Zone::query()
            ->select(['id', 'name'])
            ->active()
            ->ofType($type)
            ->orderBy('name')
            ->get()
            ->map->only(['id', 'name'])
            ->all());

        return Zone::hydrate($rows);
    }

    /** @return SupportCollection<string, int> Active listing counts keyed by property type. */
    public function listingTypeCounts(): SupportCollection
    {
        $counts = Cache::remember('home.listing_type_counts', self::CACHE_TTL, fn () => PropertyListing::query()
            ->where('status', 'active')
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all());

        return collect($counts);
    }

    /** @return Collection<int, Agent> */
    public function topAgents(): Collection
    {
        // Ranking every agent by active-listing count is the expensive part
        // (correlated COUNT per row, full-table sort). Cache the winning IDs and
        // re-fetch just those rows — cheap indexed lookups that keep the models
        // and relations fresh without pushing objects through the cache store.
        $ids = Cache::remember('home.top_agent_ids', self::CACHE_TTL, fn () => Agent::query()
            ->where('is_active', true)
            ->withCount(['listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->orderByDesc('listings_count')
            ->orderBy('id')
            ->limit(4)
            ->pluck('id')
            ->all());

        if ($ids === []) {
            return new Collection;
        }

        return Agent::query()
            ->with(['user:id,name,avatar', 'agency:id,name'])
            ->withCount(['listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Agent $agent) => array_search($agent->id, $ids))
            ->values();
    }

    /** @return Collection<int, Agency> */
    public function topAgencies(): Collection
    {
        // Same ID-cache pattern as topAgents(): the full-table rank is cached,
        // the 4 rendered rows are re-fetched fresh.
        $ids = Cache::remember('home.top_agency_ids', self::CACHE_TTL, fn () => Agency::query()
            ->where('status', 'active')
            ->orderByDesc('is_verified')
            ->orderByDesc('rating')
            ->orderBy('id')
            ->limit(4)
            ->pluck('id')
            ->all());

        if ($ids === []) {
            return new Collection;
        }

        return Agency::query()
            ->with('zone:id,name')
            ->withCount(['agents', 'listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Agency $agency) => array_search($agency->id, $ids))
            ->values();
    }

    /** @return Collection<int, Zone> */
    public function popularZones(): Collection
    {
        // Cache the plain attribute rows (the store rejects serialized objects), then
        // rehydrate them into Zone models — keeping listings_count as an attribute.
        $rows = Cache::remember('home.popular_zones', self::CACHE_TTL, fn () => Zone::query()
            ->select(['id', 'name', 'slug', 'type'])
            ->active()
            ->whereIn('type', ['city', 'area'])
            ->whereHas('listings', fn (Builder $query) => $query->where('status', 'active'))
            ->withCount(['listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->orderByDesc('listings_count')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map->only(['id', 'name', 'slug', 'type', 'listings_count'])
            ->all());

        return Zone::hydrate($rows);
    }

    /** @return array{min: int|null, max: int|null} */
    public function activeListingPriceBounds(): array
    {
        return Cache::remember('home.price_bounds', self::CACHE_TTL, function () {
            $bounds = PropertyListing::query()
                ->where('status', 'active')
                ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                ->first();

            return [
                'min' => $bounds?->min_price !== null ? (int) $bounds->min_price : null,
                'max' => $bounds?->max_price !== null ? (int) $bounds->max_price : null,
            ];
        });
    }

    /** @return array{listings: int, clients: int, agents: int, rating: float} */
    public function statisticValues(): array
    {
        return Cache::remember('home.stats', 900, fn () => [
            'listings' => PropertyListing::query()->where('status', 'active')->count(),
            'clients' => User::query()->count(),
            'agents' => Agent::query()->where('is_active', true)->count(),
            'rating' => (float) (Review::query()->where('is_visible', true)->avg('rating') ?? 0),
        ]);
    }
}
