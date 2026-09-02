<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\PropertySearchRequest;
use App\Models\Block;
use App\Models\PropertyListing;
use App\Models\Zone;
use App\Services\DistanceService;
use App\Services\NearbyAmenitiesService;
use App\Services\Payment\GatewayFactory;
use App\Services\PointInPolygon;
use App\Services\SchemaMarkupService;
use App\Services\ValuationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyController extends Controller
{
    public function index(PropertySearchRequest $request, PointInPolygon $pointInPolygon): View
    {
        $filters = $request->validated();

        if (! $request->has('zone_id') && session('zone_id')) {
            $filters['zone_id'] = (int) session('zone_id');
        }

        $query = $this->applySorting(
            $this->searchQuery($filters),
            $filters['sort'] ?? 'newest',
            isset($filters['near_lat']) ? (float) $filters['near_lat'] : null,
            isset($filters['near_lng']) ? (float) $filters['near_lng'] : null,
        );
        $polygon = $this->decodePolygon($filters['polygon'] ?? null);

        $listings = $polygon
            ? $this->paginateWithinPolygon($query, $polygon, $pointInPolygon, $request)
            : $query->paginate(12)->withQueryString();

        $zones = $this->searchableZones();
        $nearbyZones = $listings->isEmpty() ? $this->nearbyZoneSuggestions($filters['zone_id'] ?? null) : collect();

        return view('website.sections.all_properties', compact('filters', 'listings', 'zones', 'polygon', 'nearbyZones'));
    }

    public function show(string $property, SchemaMarkupService $schemaMarkup, NearbyAmenitiesService $nearbyAmenities, GatewayFactory $gateways): View|RedirectResponse
    {
        if ($redirect = $this->redirectLegacyId($property, 'properties.show')) {
            return $redirect;
        }

        $listing = PropertyListing::query()
            ->with([
                'zone.parent:id,name,parent_id',
                'owner:id,name,avatar,verification_status,phone_visibility,created_at',
                'agency:id,name,slug,logo,is_verified',
                'galleryImages:id,property_listing_id,path,disk,is_cover,is_floor_plan,sort_order',
                'floorPlanImages:id,property_listing_id,path,disk,is_cover,is_floor_plan,sort_order',
                'videos:id,property_listing_id,url,source,thumbnail,sort_order',
                'customFieldValues.field',
                'reviews' => fn ($query) => $query->with('reviewer:id,name,avatar')->where('is_visible', true)->latest()->limit(6),
                'verificationCase.scores',
                'disclosures',
                'dealScores' => fn ($query) => $query->latest('computed_at')->limit(1),
                'valuations' => fn ($query) => $query->latest('valued_at')->limit(1),
                'riskAssessments',
                'comparableProperties' => fn ($query) => $query->orderByDesc('similarity_score')->limit(6),
                'priceHistory',
                'timelineEvents',
            ])
            ->withCount(['reviews' => fn ($query) => $query->where('is_visible', true)])
            ->withAvg(['reviews' => fn ($query) => $query->where('is_visible', true)], 'rating')
            ->where('status', 'active')
            ->where('slug', $property)
            ->firstOrFail();

        // Build the intelligence snapshot (valuation, comparables, risk, Deal Score)
        // lazily on first view so pages don't pay for analysis until one is needed.
        $this->ensureIntelligence($listing);

        // Defer the view-count write until after the response is flushed so a
        // hot listing's UPDATE never sits on the read path (row-lock contention).
        defer(fn () => PropertyListing::whereKey($listing->id)->increment('view_count'));

        $similarListings = PropertyListing::query()
            ->with(['zone:id,name', 'owner:id,name,avatar', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])
            ->withViewerFavorite()
            ->where('status', 'active')
            ->whereKeyNot($listing->id)
            ->where('type', $listing->type)
            ->where(fn (Builder $query) => $query->where('zone_id', $listing->zone_id)->orWhereBetween('price', [
                (int) ($listing->price * 0.75),
                (int) ($listing->price * 1.25),
            ]))
            ->latest('published_at')
            ->limit(3)
            ->get();

        $schema = $schemaMarkup->forListing($listing);
        $isFavorited = auth()->check() && auth()->user()->favorites()->where('property_listing_id', $listing->id)->exists();
        $isCompared = auth()->check() && auth()->user()->compares()->where('property_listing_id', $listing->id)->exists();
        $ownerBlock = auth()->check() && $listing->owner_id
            ? Block::where('blocker_id', auth()->id())->where('blocked_id', $listing->owner_id)->first()
            : null;
        $nearby = $listing->lat && $listing->lng ? $nearbyAmenities->near((float) $listing->lat, (float) $listing->lng) : [];
        $activeGateways = $gateways->activeGateways();
        $isOwner = auth()->check() && auth()->id() === $listing->owner_id;
        $documents = $isOwner ? $listing->documents()->get() : collect();

        $dealScore = $listing->dealScores->first();
        $valuation = $listing->valuations->first();
        $marketValue = $valuation?->estimated_value;
        $displayCurrency = session('currency_code')
            ?? (auth()->check() ? auth()->user()->currency : null)
            ?? $listing->currency_code;
        $discountPct = app(ValuationService::class)->discountPct($listing, $marketValue, $displayCurrency);
        $riskAssessments = $listing->riskAssessments;
        $comparables = $listing->comparableProperties;
        $priceHistory = $listing->priceHistory;
        $timelineEvents = $listing->timelineEvents;

        $owner = $listing->owner;
        $sellerReputation = $owner ? app(\App\Services\SellerReputationService::class)->statsFor($owner) : null;

        return view('website.pages.property_details', compact(
            'listing', 'similarListings', 'schema', 'isFavorited', 'isCompared', 'nearby',
            'ownerBlock', 'activeGateways', 'isOwner', 'documents',
            'dealScore', 'valuation', 'marketValue', 'discountPct',
            'riskAssessments', 'comparables', 'priceHistory', 'timelineEvents',
            'owner', 'sellerReputation',
        ));
    }

    /**
     * Build the intelligence snapshot (valuation, comparables, risk, Deal Score) the
     * first time a listing is viewed. Subsequent views reuse the stored snapshot.
     */
    private function ensureIntelligence(PropertyListing $listing): void
    {
        if ($listing->dealScores()->exists()) {
            return;
        }

        $currencyCode = session('currency_code')
            ?? (auth()->check() ? auth()->user()->currency : null)
            ?? $listing->currency_code;

        app(\App\Services\IntelligenceService::class)->analyze($listing, $currencyCode);
        $listing->load([
            'dealScores' => fn ($query) => $query->latest('computed_at')->limit(1),
            'valuations' => fn ($query) => $query->latest('valued_at')->limit(1),
            'riskAssessments',
            'comparableProperties' => fn ($query) => $query->orderByDesc('similarity_score')->limit(6),
        ]);
    }

    /** Permanently redirect a legacy numeric-ID URL to its canonical slug URL. */
    private function redirectLegacyId(string $property, string $routeName): ?RedirectResponse
    {
        if (ctype_digit($property) && $slug = PropertyListing::whereKey($property)->value('slug')) {
            return redirect()->route($routeName, $slug, 301);
        }

        return null;
    }

    public function distance(Request $request, string $property, DistanceService $distanceService): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $listing = PropertyListing::query()->where('status', 'active')->whereNotNull('lat')->whereNotNull('lng')->where('slug', $property)->firstOrFail();

        $distanceKm = $distanceService->haversineKm(
            (float) $request->input('lat'),
            (float) $request->input('lng'),
            (float) $listing->lat,
            (float) $listing->lng,
        );

        return response()->json([
            'distance' => $distanceService->format($distanceKm),
            'drive_minutes' => $distanceService->estimatedDriveMinutes($distanceKm),
            'walk_minutes' => $distanceService->estimatedWalkMinutes($distanceKm),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function searchQuery(array $filters): Builder
    {
        return PropertyListing::query()
            ->with([
                'zone:id,name',
                'owner:id,name,avatar',
                'coverImage:id,property_listing_id,path,disk,is_cover,sort_order',
            ])
            ->withViewerFavorite()
            ->withAvg(['reviews as reviews_avg_rating' => fn (Builder $query) => $query->where('is_visible', true)], 'rating')
            ->where('status', 'active')
            ->when($filters['zone_id'] ?? null, fn (Builder $query, int $zoneId) => $query->where('zone_id', $zoneId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('price', '<=', $filters['max_price']))
            ->when(isset($filters['bedrooms']), fn (Builder $query) => $query->where('bedrooms', '>=', $filters['bedrooms']))
            ->when(isset($filters['bathrooms']), fn (Builder $query) => $query->where('bathrooms', '>=', $filters['bathrooms']))
            ->when($filters['allowed_for'] ?? null, fn (Builder $query, string $allowedFor) => $query->whereIn('allowed_for', [$allowedFor, 'both']))
            ->when(isset($filters['furnished']), fn (Builder $query) => $query->where('furnished', (bool) $filters['furnished']))
            ->when(isset($filters['parking']), fn (Builder $query) => $query->where('parking', (bool) $filters['parking']))
            ->when(isset($filters['floor']), fn (Builder $query) => $query->where('floor', $filters['floor']))
            ->when(isset($filters['advance_months']), fn (Builder $query) => $query->where('advance_months', '<=', $filters['advance_months']))
            ->when($filters['featured'] ?? false, fn (Builder $query) => $query->where('is_featured', true))
            ->when($filters['verified'] ?? false, fn (Builder $query) => $query->where('is_verified', true))
            ->when($filters['verification_status'] ?? null, fn (Builder $query, string $status) => $query->verificationStatus($status))
            ->when($filters['deal_tag'] ?? null, fn (Builder $query, string $tag) => $query->dealTag($tag))
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword): void {
                $search = '%'.addcslashes($keyword, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('address', 'like', $search));
            })
            ->when($filters['sw_lat'] ?? null, function (Builder $query) use ($filters): void {
                $query->whereBetween('lat', [(float) $filters['sw_lat'], (float) $filters['ne_lat']])
                    ->whereBetween('lng', [(float) $filters['sw_lng'], (float) $filters['ne_lng']]);
            });
    }

    /**
     * @return list<array{0: float, 1: float}>|null
     */
    private function decodePolygon(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        $points = json_decode($raw, true);

        if (! is_array($points) || count($points) < 3) {
            return null;
        }

        $polygon = [];

        foreach ($points as $point) {
            if (! is_array($point) || count($point) !== 2) {
                return null;
            }

            $polygon[] = [(float) $point[0], (float) $point[1]];
        }

        return $polygon;
    }

    /**
     * The point-in-polygon test runs in PHP, so narrow the candidate set in SQL first with the
     * polygon's bounding box. The box is a strict superset of the polygon, so results are identical
     * while the number of hydrated models drops from "every active listing" to "listings near the box".
     * `whereBetween` also discards NULL coordinates, which the PHP filter rejected anyway.
     *
     * @param  list<array{0: float, 1: float}>  $polygon
     */
    private function paginateWithinPolygon(Builder $query, array $polygon, PointInPolygon $pointInPolygon, Request $request): LengthAwarePaginator
    {
        $latitudes = array_column($polygon, 0);
        $longitudes = array_column($polygon, 1);

        $matches = $query
            ->whereBetween('lat', [min($latitudes), max($latitudes)])
            ->whereBetween('lng', [min($longitudes), max($longitudes)])
            ->limit(1000)
            ->get()
            ->filter(
                fn (PropertyListing $listing) => $listing->lat !== null && $listing->lng !== null
                    && $pointInPolygon->contains($polygon, (float) $listing->lat, (float) $listing->lng)
            )->values();

        $perPage = 12;
        $page = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator(
            $matches->forPage($page, $perPage)->values(),
            $matches->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function applySorting(Builder $query, string $sort, ?float $nearLat = null, ?float $nearLng = null): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('id'),
            'deal_score' => $query->orderByDesc('deal_score_cached')->orderByDesc('id'),
            'distance' => $nearLat !== null && $nearLng !== null
                ? $query->orderByRaw(
                    '(POW(lat - ?, 2) + POW(lng - ?, 2)) ASC',
                    [$nearLat, $nearLng]
                )
                : $query->orderByDesc('published_at')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * Sibling zones (same parent) with active listings, suggested when a search
     * comes back empty — falls back to the parent zone if no sibling has listings.
     *
     * @return Collection<int, Zone>
     */
    private function nearbyZoneSuggestions(?int $zoneId): Collection
    {
        if (! $zoneId || ! $zone = Zone::find($zoneId)) {
            return collect();
        }

        $siblings = Zone::query()
            ->where('id', '!=', $zone->id)
            ->where('parent_id', $zone->parent_id)
            ->whereHas('listings', fn (Builder $query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug']);

        if ($siblings->isNotEmpty() || ! $zone->parent_id) {
            return $siblings;
        }

        return Zone::query()
            ->whereKey($zone->parent_id)
            ->whereHas('listings', fn (Builder $query) => $query->where('status', 'active'))
            ->get(['id', 'name', 'slug']);
    }

    /** @return Collection<int, Zone> */
    private function searchableZones(): Collection
    {
        return Zone::query()
            ->select(['id', 'name', 'type'])
            ->active()
            ->whereIn('type', ['city', 'area'])
            ->whereHas('listings', fn (Builder $query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get();
    }
}
