<?php

namespace App\Http\Controllers\Api\V1\Zone;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\ZoneResource;
use App\Models\Zone;
use App\Services\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ZoneController extends ApiController
{
    /**
     * GET /api/v1/zones
     *
     * Query parameters:
     *   parent_id   – filter to children of a zone
     *   type        – country|state|city|area
     *   search      – name substring match
     *   active_only – 0|1, default 1
     *   mode        – flat (default, paginated) | tree (nested, cached)
     *   per_page    – default 50, max 200
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'parent_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'type' => ['sometimes', Rule::in(['country', 'state', 'city', 'area'])],
            'search' => ['sometimes', 'string', 'max:100'],
            'active_only' => ['sometimes', 'boolean'],
            'mode' => ['sometimes', Rule::in(['flat', 'tree'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $mode = $request->input('mode', 'flat');

        if ($mode === 'tree') {
            return $this->treeResponse($request);
        }

        return $this->flatResponse($request);
    }

    /**
     * GET /api/v1/zones/countries
     *
     * Shortcut for active top-level country zones. Cached for 1 day.
     */
    public function countries(): JsonResponse
    {
        $data = Cache::remember('api.zones.countries', now()->addDay(), function () {
            $zones = Zone::active()
                ->ofType('country')
                ->withCount('children')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return ZoneResource::collection($zones)->resolve();
        });

        return $this->success($data);
    }

    /**
     * GET /api/v1/zones/{zone}/areas
     *
     * All active area-type children under a given parent zone (city or state).
     * Cached per parent zone.
     */
    public function areas(Zone $zone): JsonResponse
    {
        $data = Cache::remember("api.zones.{$zone->id}.areas", now()->addDay(), function () use ($zone) {
            $areas = Zone::active()
                ->where('parent_id', $zone->id)
                ->ofType('area')
                ->withCount('children')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return ZoneResource::collection($areas)->resolve();
        });

        return $this->success($data);
    }

    /**
     * GET /api/v1/zones/{zone}
     *
     * Single zone with its immediate children and parent breadcrumb.
     */
    public function show(Zone $zone): JsonResponse
    {
        $cacheKey = "api.zones.show.{$zone->id}";

        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($zone) {
            $zone->load(['parent', 'children' => function ($q) {
                $q->withCount('children')->orderBy('sort_order')->orderBy('name');
            }]);

            return (new ZoneResource($zone))->resolve();
        });

        return $this->success($data);
    }

    /**
     * GET /api/v1/zones/resolve?lat=23.77&lng=90.36&type=area&radius=50
     *
     * Finds the nearest active zone to the supplied coordinate.
     * Uses a bounding-box pre-filter then Haversine ORDER BY for accuracy.
     *
     * Query parameters:
     *   lat    – required, decimal
     *   lng    – required, decimal
     *   type   – country|state|city|area (default: area)
     *   radius – bounding-box search radius in km (default: 50)
     */
    public function resolve(Request $request, DistanceService $distanceService): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['sometimes', Rule::in(['country', 'state', 'city', 'area'])],
            'radius' => ['sometimes', 'numeric', 'min:1', 'max:500'],
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $type = $request->input('type', 'area');
        $radius = (float) $request->input('radius', 50);

        // Bounding-box pre-filter dramatically reduces rows scanned before Haversine
        $box = $distanceService->boundingBox($lat, $lng, $radius);

        $zone = Zone::query()
            ->selectRaw(
                '*, (6371 * acos(
                    cos(radians(?)) * cos(radians(lat)) *
                    cos(radians(lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(lat))
                )) AS distance',
                [$lat, $lng, $lat]
            )
            ->where('type', $type)
            ->where('is_active', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('lat', [$box['lat_min'], $box['lat_max']])
            ->whereBetween('lng', [$box['lng_min'], $box['lng_max']])
            ->orderBy('distance')
            ->first();

        // If nothing found in bounding box, widen search without the box constraint
        if (! $zone) {
            $zone = Zone::nearest($lat, $lng, $type);
        }

        if (! $zone) {
            return $this->error('No matching zone found near the supplied coordinates.', 404);
        }

        $zone->load('parent');

        return $this->success(new ZoneResource($zone));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function flatResponse(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 50), 200);
        $activeOnly = filter_var($request->input('active_only', true), FILTER_VALIDATE_BOOLEAN);
        $cacheParams = http_build_query($request->only(['parent_id', 'type', 'search', 'active_only', 'per_page', 'page']));
        $cacheKey = 'api.zones.flat.'.md5($cacheParams);

        // Skip cache when searching (dynamic, low benefit)
        if ($request->filled('search')) {
            return $this->buildFlatPaginated($request, $perPage, $activeOnly);
        }

        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($request, $perPage, $activeOnly) {
            return $this->buildFlatPaginated($request, $perPage, $activeOnly, asPrimitive: true);
        });

        // If cached as primitive (array), return directly
        if (is_array($data)) {
            return response()->json(['success' => true, 'message' => '', 'data' => $data['data'], 'errors' => null, 'meta' => $data['meta']]);
        }

        return $data;
    }

    private function buildFlatPaginated(Request $request, int $perPage, bool $activeOnly, bool $asPrimitive = false): mixed
    {
        $query = Zone::query()->withCount('children');

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } else {
            // Default to root zones when no parent_id is supplied and no type filter
            if (! $request->filled('type')) {
                $query->whereNull('parent_id');
            }
        }

        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($activeOnly) {
            $query->active();
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.addcslashes($request->input('search'), '%_\\').'%');
        }

        $query->orderBy('sort_order')->orderBy('name');

        $paginator = $query->paginate($perPage);

        if ($asPrimitive) {
            return [
                'data' => ZoneResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        }

        return $this->paginated($paginator, ZoneResource::class);
    }

    private function treeResponse(Request $request): JsonResponse
    {
        $type = $request->input('type', 'country');
        $activeOnly = filter_var($request->input('active_only', true), FILTER_VALIDATE_BOOLEAN);
        $cacheKey = "api.zones.tree.{$type}.".($activeOnly ? 'active' : 'all');

        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($type, $activeOnly) {
            $query = Zone::query()->whereNull('parent_id');

            if ($type !== 'all') {
                $query->ofType($type);
            }

            if ($activeOnly) {
                $query->active();
            }

            $roots = $query->with('allChildren')->orderBy('sort_order')->orderBy('name')->get();

            return ZoneResource::collection($roots)->resolve();
        });

        return $this->success($data);
    }
}
