<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Enums\PropertyType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Listing\StoreListingRequest;
use App\Http\Requests\Api\V1\Listing\UpdateListingRequest;
use App\Http\Requests\Api\V1\Listing\UpdateListingStatusRequest;
use App\Http\Requests\Api\V1\Listing\UploadMediaRequest;
use App\Http\Resources\Api\V1\ListingDetailResource;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\PropertyVideo;
use App\Models\Zone;
use App\Services\DistanceService;
use App\Services\NearbyAmenitiesService;
use App\Services\PostEntitlementService;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListingController extends ApiController
{
    // ── Public ────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/listings
     *
     * Paginated listing search with filters, sorting, map-bounds support,
     * and facet counts in meta.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'include_children' => ['sometimes', 'boolean'],
            'type' => ['sometimes', Rule::in(PropertyType::values())],
            'status' => ['sometimes', Rule::in(['pending', 'active', 'rented', 'sold', 'archived', 'rejected'])],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'bedrooms' => ['sometimes', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'integer', 'min:0'],
            'allowed_for' => ['sometimes', Rule::in(['bachelor', 'family', 'both'])],
            'furnished' => ['sometimes', 'boolean'],
            'parking' => ['sometimes', 'boolean'],
            'floor' => ['sometimes', 'integer'],
            'advance_months' => ['sometimes', 'integer', 'min:0'],
            'featured' => ['sometimes', 'boolean'],
            'verified' => ['sometimes', 'boolean'],
            'keyword' => ['sometimes', 'string', 'max:200'],
            'sort' => ['sometimes', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'sw_lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:sw_lng,ne_lat,ne_lng'],
            'sw_lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:sw_lat,ne_lat,ne_lng'],
            'ne_lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:sw_lat,sw_lng,ne_lng'],
            'ne_lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:sw_lat,sw_lng,ne_lat'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = PropertyListing::query()
            ->withCount(['reviews', 'favorites'])
            ->with(['zone', 'owner', 'images']);

        // Status — default to active for public browsing
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active');
        }

        // Listing type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Owner filter
        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        // Zone — support drilling into child zones
        if ($zoneId = $request->integer('zone_id')) {
            if ($request->boolean('include_children')) {
                $zone = Zone::find($zoneId);
                if ($zone) {
                    $ids = array_merge([$zone->id], $zone->descendantIds());
                    $query->whereIn('zone_id', $ids);
                } else {
                    $query->where('zone_id', $zoneId);
                }
            } else {
                $query->where('zone_id', $zoneId);
            }
        }

        // Boolean flags
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('verified')) {
            $query->where('is_verified', true);
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->input('max_price'));
        }

        // Physical attributes
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->integer('bedrooms'));
        }
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', $request->integer('bathrooms'));
        }
        if ($request->filled('allowed_for')) {
            $query->where('allowed_for', $request->input('allowed_for'));
        }
        if ($request->has('furnished')) {
            $query->where('furnished', $request->boolean('furnished'));
        }
        if ($request->has('parking')) {
            $query->where('parking', $request->boolean('parking'));
        }
        if ($request->filled('floor')) {
            $query->where('floor', $request->integer('floor'));
        }
        if ($request->filled('advance_months')) {
            $query->where('advance_months', '<=', $request->integer('advance_months'));
        }

        // Keyword full-text search across title, description, address
        if ($request->filled('keyword')) {
            $kw = '%'.addcslashes($request->input('keyword'), '%_\\').'%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $kw)
                ->orWhere('description', 'like', $kw)
                ->orWhere('address', 'like', $kw)
            );
        }

        // Map-bounds filter (overrides keyword radius; both can combine)
        if ($request->filled('sw_lat')) {
            $query->whereBetween('lat', [(float) $request->input('sw_lat'), (float) $request->input('ne_lat')])
                ->whereBetween('lng', [(float) $request->input('sw_lng'), (float) $request->input('ne_lng')]);
        }

        // Drawn-area / polygon search: accept `polygon` as JSON array of points
        // Example: polygon=[{"lat":23.7,"lng":90.4}, {"lat":23.8,"lng":90.5}, ...]
        if ($polygonInput = $request->input('polygon')) {
            $points = is_string($polygonInput) ? json_decode($polygonInput, true) : $polygonInput;

            if (is_array($points) && count($points) >= 3) {
                // Normalize to ['lat' => float, 'lng' => float]
                $coords = [];
                foreach ($points as $p) {
                    if (is_array($p) && array_key_exists('lat', $p) && array_key_exists('lng', $p)) {
                        $coords[] = ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']];
                    } elseif (is_array($p) && isset($p[0]) && isset($p[1])) {
                        $coords[] = ['lat' => (float) $p[0], 'lng' => (float) $p[1]];
                    }
                }

                if (count($coords) >= 3) {
                    $lats = array_column($coords, 'lat');
                    $lngs = array_column($coords, 'lng');

                    // Narrow by bounding box first for performance
                    $query->whereBetween('lat', [min($lats), max($lats)])
                        ->whereBetween('lng', [min($lngs), max($lngs)]);

                    // Build WKT POLYGON string (WKT uses lon lat order)
                    $wktPoints = array_map(fn ($c) => $c['lng'].' '.$c['lat'], $coords);
                    if (reset($wktPoints) !== end($wktPoints)) {
                        $wktPoints[] = reset($wktPoints);
                    }

                    $wkt = 'POLYGON(('.implode(',', $wktPoints).'))';

                    // Use spatial contains check (requires MySQL/PostGIS spatial support).
                    // Falls back to the bounding-box filter when DB does not support spatial functions.
                    try {
                        $query->whereRaw(
                            "ST_Contains(ST_GeomFromText(?), ST_PointFromText(CONCAT('POINT(', property_listings.lng, ' ', property_listings.lat, ')')))",
                            [$wkt]
                        );
                    } catch (\Throwable $e) {
                        // Ignore and keep bounding-box filter only
                    }
                }
            }
        }

        // Sorting
        match ($request->input('sort', 'newest')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        // Facets — counted on the same base query before pagination
        $facets = $this->buildFacets(clone $query);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        $resource = ListingResource::collection($paginator);

        $meta = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'path' => $paginator->path(),
            'facets' => $facets,
        ];

        if ($paginator->total() === 0) {
            $meta['empty_state'] = $this->emptyStateHints($request->integer('zone_id') ?: null);
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $resource->resolve(),
            'errors' => null,
            'meta' => $meta,
        ]);
    }

    /**
     * Suggestions shown when a search returns zero results: sibling/parent zones
     * that do have active listings, and whether saved-search alerts are available
     * so the client can prompt "no matches — get notified when one appears".
     *
     * @return array{nearby_zones: array<int, array{id: int, name: string}>, alerts_available: bool}
     */
    private function emptyStateHints(?int $zoneId): array
    {
        $nearbyZones = collect();

        if ($zoneId && $zone = Zone::find($zoneId)) {
            // Prefer siblings under the same parent (same city/area tier); fall back
            // to the parent zone itself if there are no populated siblings.
            $nearbyZones = Zone::query()
                ->where('id', '!=', $zone->id)
                ->where('parent_id', $zone->parent_id)
                ->whereHas('listings', fn ($q) => $q->where('status', 'active'))
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name']);

            if ($nearbyZones->isEmpty() && $zone->parent_id) {
                $nearbyZones = Zone::query()
                    ->whereKey($zone->parent_id)
                    ->whereHas('listings', fn ($q) => $q->where('status', 'active'))
                    ->get(['id', 'name']);
            }
        }

        return [
            'nearby_zones' => $nearbyZones->map(fn (Zone $z) => ['id' => $z->id, 'name' => $z->name])->values()->all(),
            'alerts_available' => (bool) setting('saved_search_alert_enabled', true),
        ];
    }

    /**
     * GET /api/v1/listings/{listing}
     *
     * Full listing detail. Active listings are public; owner can view any status.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $query = PropertyListing::withCount(['reviews', 'favorites'])
            ->with([
                'zone.parent',
                'owner',
                'agency',
                'images',
                'videos',
                'customFieldValues.field',
                'reviews' => fn ($q) => $q->with('reviewer')->where('is_visible', true)->latest()->limit(10),
            ]);

        // Authenticated owner may view their own listing at any status
        $user = $request->user('sanctum');
        if ($user) {
            $listing = $query->where(fn ($q) => $q
                ->where('status', 'active')
                ->orWhere('owner_id', $user->id)
            )->find($id);
        } else {
            $listing = $query->where('status', 'active')->find($id);
        }

        if (! $listing) {
            return $this->error('Listing not found.', 404);
        }

        $listing->increment('view_count');

        $data = (new ListingDetailResource($listing))->toArray($request);

        $data['nearby_amenities'] = $listing->lat && $listing->lng
            ? app(NearbyAmenitiesService::class)->near((float) $listing->lat, (float) $listing->lng)
            : [];

        return $this->success($data);
    }

    // ── Authenticated ─────────────────────────────────────────────────────────

    /**
     * POST /api/v1/listings
     *
     * Create a new listing. Enforces post-limit guard before creation.
     */
    public function store(StoreListingRequest $request, PostEntitlementService $entitlements): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', PropertyListing::class);

        if (! $entitlements->canUserPost($user)) {
            $summary = $entitlements->summary($user);

            return $this->error('You have reached your listing limit. Upgrade your plan to post more listings.', 403, [
                'post_limit' => $summary['post_limit'],
                'used_posts' => $summary['used_posts'],
                'remaining_posts' => $summary['remaining_posts'],
                'active_subscription' => $summary['active_subscription'] ? [
                    'plan' => $summary['active_subscription']->plan?->name,
                    'ends_at' => $summary['active_subscription']->ends_at?->toIso8601String(),
                ] : null,
            ]);
        }

        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $listing = PropertyListing::create(array_merge($validated, [
            'owner_id' => $user->id,
            'status' => 'pending',
            'published_at' => now(),
        ]));

        $this->syncCustomFields($listing, $customFields);

        $listing->load(['zone', 'owner', 'agency', 'images', 'videos', 'customFieldValues.field']);

        return $this->created(new ListingDetailResource($listing), 'Listing submitted for review.');
    }

    /**
     * PUT /api/v1/listings/{listing}
     */
    public function update(UpdateListingRequest $request, PropertyListing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? null;
        unset($validated['custom_fields']);

        $listing->update($validated);

        if ($customFields !== null) {
            $this->syncCustomFields($listing, $customFields);
        }

        $listing->load(['zone', 'owner', 'agency', 'images', 'videos', 'customFieldValues.field']);

        return $this->success(new ListingDetailResource($listing), 'Listing updated.');
    }

    /**
     * DELETE /api/v1/listings/{listing}
     */
    public function destroy(PropertyListing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return $this->success(null, 'Listing deleted.');
    }

    /**
     * POST /api/v1/listings/{listing}/media
     *
     * Upload images and/or add video URLs to a listing.
     */
    public function uploadMedia(UploadMediaRequest $request, PropertyListing $listing): JsonResponse
    {
        $this->authorize('uploadMedia', $listing);

        $newImages = [];

        if ($request->hasFile('images')) {
            $existingCount = $listing->images()->count();
            $coverIndex = $request->integer('cover_index', -1);

            // If cover_index is provided in this batch, clear existing covers first
            if ($coverIndex >= 0) {
                $listing->images()->update(['is_cover' => false]);
            }

            foreach ($request->file('images') as $i => $file) {
                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs("listings/{$listing->id}", $filename, 'public');

                $isCover = ($coverIndex >= 0)
                    ? ($i === $coverIndex)
                    : ($existingCount === 0 && $i === 0);

                $image = PropertyImage::create([
                    'property_listing_id' => $listing->id,
                    'path' => $path,
                    'disk' => 'public',
                    'is_cover' => $isCover,
                    'sort_order' => $existingCount + $i,
                ]);

                $newImages[] = [
                    'id' => $image->id,
                    'url' => asset('storage/'.$image->path),
                    'is_cover' => $image->is_cover,
                    'sort_order' => $image->sort_order,
                ];
            }
        }

        $newVideos = [];

        if ($request->filled('videos')) {
            $existingCount = $listing->videos()->count();

            foreach ($request->input('videos') as $i => $videoData) {
                $video = PropertyVideo::create([
                    'property_listing_id' => $listing->id,
                    'url' => $videoData['url'],
                    'source' => $videoData['source'] ?? null,
                    'thumbnail' => $videoData['thumbnail'] ?? null,
                    'sort_order' => $existingCount + $i,
                ]);

                $newVideos[] = [
                    'id' => $video->id,
                    'url' => $video->url,
                    'source' => $video->source,
                    'thumbnail' => $video->thumbnail,
                    'sort_order' => $video->sort_order,
                ];
            }
        }

        return $this->success([
            'images' => $newImages,
            'videos' => $newVideos,
        ], 'Media uploaded successfully.');
    }

    /**
     * DELETE /api/v1/listings/{listing}/media/{type}/{mediaId}
     *
     * Remove a single image or video from a listing.
     */
    public function deleteMedia(PropertyListing $listing, string $type, int $mediaId): JsonResponse
    {
        $this->authorize('deleteMedia', $listing);

        if ($type === 'image') {
            $image = PropertyImage::where('property_listing_id', $listing->id)->find($mediaId);

            if (! $image) {
                return $this->error('Image not found on this listing.', 404);
            }

            Storage::disk('public')->delete($image->path);
            $image->delete();

            // If we just removed the cover, promote the next image
            if ($image->is_cover) {
                $next = $listing->images()->first();
                $next?->update(['is_cover' => true]);
            }

            return $this->success(null, 'Image deleted.');
        }

        if ($type === 'video') {
            $video = PropertyVideo::where('property_listing_id', $listing->id)->find($mediaId);

            if (! $video) {
                return $this->error('Video not found on this listing.', 404);
            }

            $video->delete();

            return $this->success(null, 'Video deleted.');
        }

        return $this->error('Invalid media type. Use "image" or "video".', 422);
    }

    /**
     * PATCH /api/v1/listings/{listing}/status
     *
     * Owner marks a listing as rented, sold, or archived.
     */
    public function updateStatus(UpdateListingStatusRequest $request, PropertyListing $listing): JsonResponse
    {
        $this->authorize('updateStatus', $listing);

        $listing->update(['status' => $request->validated('status')]);

        return $this->success(['status' => $listing->status], 'Listing status updated.');
    }

    /**
     * POST /api/v1/listings/{listing}/freshness-confirm
     *
     * Owner confirms the listing is still accurate and available.
     */
    public function confirmFreshness(PropertyListing $listing): JsonResponse
    {
        $this->authorize('confirmFreshness', $listing);

        $listing->update([
            'last_freshness_check_at' => now(),
            'needs_confirmation_at' => null,
        ]);

        return $this->success(null, 'Listing freshness confirmed.');
    }

    /**
     * GET /api/v1/listings/{listing}/distance
     *
     * Straight-line (Haversine) distance from the listing to a user-supplied coordinate.
     */
    public function distance(Request $request, DistanceService $distanceService, int $id): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $listing = PropertyListing::where('id', $id)
            ->where('status', 'active')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first();

        if (! $listing) {
            return $this->error('Listing not found or location is unavailable.', 404);
        }

        $userLat = (float) $request->input('lat');
        $userLng = (float) $request->input('lng');

        $distanceKm = $distanceService->haversineKm(
            $listing->lat,
            $listing->lng,
            $userLat,
            $userLng
        );

        return $this->success([
            'listing_id' => $listing->id,
            'listing_coordinates' => [
                'lat' => (float) $listing->lat,
                'lng' => (float) $listing->lng,
            ],
            'user_coordinates' => [
                'lat' => $userLat,
                'lng' => $userLng,
            ],
            'distance' => [
                'km' => round($distanceKm, 2),
                'formatted' => $distanceService->format($distanceKm),
                'note' => 'Straight-line distance (crow-flies).',
            ],
            'estimated_travel' => [
                'drive' => [
                    'minutes' => $distanceService->estimatedDriveMinutes($distanceKm),
                    'label' => $distanceService->estimatedDriveMinutes($distanceKm).' min drive',
                ],
                'walk' => [
                    'minutes' => $distanceService->estimatedWalkMinutes($distanceKm),
                    'label' => $distanceService->estimatedWalkMinutes($distanceKm).' min walk',
                ],
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Sync custom field values for a listing.
     * Accepts {field_key: value} or {field_id: value} map.
     *
     * @param  array<string|int, mixed>  $fields
     */
    private function syncCustomFields(PropertyListing $listing, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        // Resolve keys → custom_field_id
        $keyMap = CustomField::active()
            ->forType($listing->type)
            ->get()
            ->keyBy('key');

        foreach ($fields as $keyOrId => $value) {
            $field = is_numeric($keyOrId)
                ? $keyMap->firstWhere('id', (int) $keyOrId)
                : $keyMap->get($keyOrId);

            if (! $field) {
                continue;
            }

            CustomFieldValue::updateOrCreate(
                ['property_listing_id' => $listing->id, 'custom_field_id' => $field->id],
                ['value' => $value]
            );
        }
    }

    /**
     * Build facet counts from a cloned query (before pagination).
     *
     * @param  Builder<PropertyListing>  $query
     * @return array<string, mixed>
     */
    private function buildFacets(Builder $query): array
    {
        // Build lean facet queries from the listing query. We must strip:
        //  - eager loads + ordering (irrelevant to aggregates),
        //  - inherited SELECT columns (the `withCount(['reviews','favorites'])`
        //    subqueries + `property_listings.*`), which otherwise leave
        //    `id`/counts outside the GROUP BY → SQLSTATE[42000] 1055, AND
        //  - the SELECT bindings those subqueries carry (e.g. the polymorphic
        //    `reviewable_type = App\Models\PropertyListing` morph binding) —
        //    leaving them behind shifts every WHERE binding → SQLSTATE[HY093]
        //    "Invalid parameter number".
        $base = $query->withoutEagerLoads()->withoutGlobalScopes()->reorder();
        $baseQuery = $base->getQuery();
        $baseQuery->columns = null;
        $baseQuery->bindings['select'] = [];

        $byType = (clone $base)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $byAllowedFor = (clone $base)
            ->selectRaw('allowed_for, count(*) as total')
            ->groupBy('allowed_for')
            ->pluck('total', 'allowed_for')
            ->toArray();

        $byBedrooms = (clone $base)
            ->selectRaw('bedrooms, count(*) as total')
            ->whereNotNull('bedrooms')
            ->groupBy('bedrooms')
            ->orderBy('bedrooms')
            ->pluck('total', 'bedrooms')
            ->toArray();

        $byFurnished = (clone $base)
            ->selectRaw('furnished, count(*) as total')
            ->groupBy('furnished')
            ->pluck('total', 'furnished')
            ->toArray();

        return [
            'by_type' => $byType,
            'by_allowed_for' => $byAllowedFor,
            'by_bedrooms' => $byBedrooms,
            'by_furnished' => [
                'furnished' => (int) ($byFurnished[1] ?? 0),
                'unfurnished' => (int) ($byFurnished[0] ?? 0),
            ],
        ];
    }

    /**
     * GET /api/v1/listings/{id}/share
     *
     * Returns a shareable deep link, web URL, WhatsApp-ready text, and
     * OpenGraph meta for the given active listing.
     */
    public function share(int $id): JsonResponse
    {
        $listing = PropertyListing::where('id', $id)
            ->where('status', 'active')
            ->with(['zone', 'images'])
            ->first();

        if (! $listing) {
            return $this->error('Listing not found.', 404);
        }

        $cover = $listing->images->firstWhere('is_cover', true) ?? $listing->images->first();
        $webUrl = rtrim(config('app.url'), '/').'/listings/'.$listing->id;
        $deepLink = 'rentdo://property/'.$listing->id;
        $currency = setting('default_currency', 'BDT');
        $priceFormatted = number_format($listing->price).' '.$currency;

        $ogDescription = implode(' · ', array_filter([
            ucfirst($listing->type),
            $listing->bedrooms ? $listing->bedrooms.'BR' : null,
            $listing->area_sqft ? $listing->area_sqft.' sqft' : null,
            $listing->zone?->name,
            $priceFormatted,
        ]));

        $shareText = implode("\n", [
            '🏠 '.$listing->title,
            $ogDescription,
            $webUrl,
        ]);

        return $this->success([
            'web_url' => $webUrl,
            'deep_link' => $deepLink,
            'share_text' => $shareText,
            'og' => [
                'title' => $listing->title,
                'description' => $ogDescription,
                'image' => $cover ? asset('storage/'.$cover->path) : null,
                'url' => $webUrl,
                'type' => 'website',
            ],
        ]);
    }

    /**
     * POST /api/v1/listings/{id}/translate
     *
     * Translates the listing's title and description into the requested target
     * language via Google Cloud Translation. Only offered when an admin has
     * configured a translation API key.
     */
    public function translate(Request $request, TranslationService $translationService, int $id): JsonResponse
    {
        $request->validate([
            'target' => ['required', 'string', 'size:2'],
        ]);

        if (! $translationService->isConfigured()) {
            return $this->error('Translation is not available.', 503);
        }

        $listing = PropertyListing::where('id', $id)->where('status', 'active')->first();

        if (! $listing) {
            return $this->error('Listing not found.', 404);
        }

        $target = $request->string('target')->lower()->value();
        $source = $listing->language_tag ? substr($listing->language_tag, 0, 2) : null;

        return $this->success([
            'target' => $target,
            'title' => $translationService->translate($listing->title, $target, $source) ?? $listing->title,
            'description' => $translationService->translate($listing->description ?? '', $target, $source) ?? $listing->description,
        ]);
    }
}
