<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Technician\StoreTechnicianApplicationRequest;
use App\Http\Requests\Api\V1\Technician\UpdateTechnicianProfileRequest;
use App\Http\Resources\Api\V1\TechnicianDetailResource;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\PropertyListing;
use App\Models\Technician;
use App\Models\TechnicianCategory;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicianController extends ApiController
{
    /**
     * GET /api/v1/technicians
     *
     * Browse active, verified technicians. Filter by category, zone, sort by rating.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:technician_categories,id'],
            'zone_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'sort' => ['sometimes', Rule::in(['rating', 'hourly_rate_asc', 'hourly_rate_desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = Technician::with(['user', 'category', 'zone'])
            ->where('status', 'active')
            ->where('is_verified', true);

        if ($request->filled('category_id')) {
            $query->where('technician_category_id', $request->integer('category_id'));
        }

        if ($request->filled('zone_id')) {
            $zone = Zone::find($request->integer('zone_id'));

            if ($zone) {
                $zoneIds = array_merge([$zone->id], $zone->descendantIds());
                $query->whereIn('zone_id', $zoneIds);
            }
        }

        match ($request->input('sort', 'rating')) {
            'hourly_rate_asc' => $query->orderBy('hourly_rate'),
            'hourly_rate_desc' => $query->orderByDesc('hourly_rate'),
            default => $query->orderByDesc('rating'),
        };

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator, TechnicianResource::class);
    }

    /**
     * GET /api/v1/technicians/{technician}
     *
     * Full technician profile: skills, rate, area, rating, completed jobs count.
     */
    public function show(Technician $technician): JsonResponse
    {
        if ($technician->status !== 'active') {
            return $this->error('Technician not found.', 404);
        }

        $technician->load(['user', 'category', 'zone', 'reviews' => fn ($q) => $q->latest()->limit(5)]);
        $technician->loadCount(['bookings as completed_bookings_count' => fn ($q) => $q->where('status', 'completed')]);

        return $this->success(new TechnicianDetailResource($technician));
    }

    /**
     * GET /api/v1/technician-categories
     *
     * List all active categories ordered by sort_order.
     */
    public function categories(): JsonResponse
    {
        $categories = TechnicianCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'icon']);

        return $this->success($categories);
    }

    /**
     * GET /api/v1/technicians/suggest?zone_id=&category_id=
     *
     * Suggest technicians available for a given zone using scopeAvailableForZone.
     * Also usable via GET /listings/{id}/suggested-technicians (same logic, zone derived from listing).
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'category_id' => ['sometimes', 'integer', 'exists:technician_categories,id'],
        ]);

        $zone = Zone::findOrFail($request->integer('zone_id'));

        $query = Technician::with(['user', 'category', 'zone'])
            ->availableForZone($zone);

        if ($request->filled('category_id')) {
            $query->where('technician_category_id', $request->integer('category_id'));
        }

        $technicians = $query->orderByDesc('rating')->limit(20)->get();

        return $this->success(TechnicianResource::collection($technicians));
    }

    /**
     * POST /api/v1/technician/apply
     *
     * Submit a technician application (pending admin approval).
     * A user can only have one technician profile.
     */
    public function apply(StoreTechnicianApplicationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (Technician::where('user_id', $user->id)->exists()) {
            return $this->error('You already have a technician profile.', 409);
        }

        $technician = Technician::create([
            'user_id' => $user->id,
            'technician_category_id' => $request->integer('technician_category_id'),
            'zone_id' => $request->integer('zone_id'),
            'bio' => $request->input('bio'),
            'skills' => $request->input('skills', []),
            'available_time' => $request->input('available_time', []),
            'experience_years' => $request->integer('experience_years'),
            'hourly_rate' => $request->input('hourly_rate'),
            'status' => 'pending',
        ]);

        $technician->load(['user', 'category', 'zone']);

        return $this->created(new TechnicianDetailResource($technician), 'Application submitted and pending admin review.');
    }

    /**
     * PUT /api/v1/technician/profile
     *
     * Update the authenticated user's technician profile.
     */
    public function updateProfile(UpdateTechnicianProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $technician = Technician::where('user_id', $user->id)->first();

        if (! $technician) {
            return $this->error('You do not have a technician profile.', 404);
        }

        $technician->update(array_filter([
            'bio' => $request->has('bio') ? $request->input('bio') : null,
            'skills' => $request->has('skills') ? $request->input('skills') : null,
            'available_time' => $request->has('available_time') ? $request->input('available_time') : null,
            'experience_years' => $request->has('experience_years') ? $request->integer('experience_years') : null,
            'hourly_rate' => $request->has('hourly_rate') ? $request->input('hourly_rate') : null,
            'is_available' => $request->has('is_available') ? $request->boolean('is_available') : null,
            'zone_id' => $request->has('zone_id') ? $request->integer('zone_id') : null,
        ], fn ($v) => $v !== null));

        $technician->load(['user', 'category', 'zone']);
        $technician->loadCount(['bookings as completed_bookings_count' => fn ($q) => $q->where('status', 'completed')]);

        return $this->success(new TechnicianDetailResource($technician), 'Profile updated.');
    }

    /**
     * GET /api/v1/listings/{listing}/suggested-technicians
     *
     * Suggest technicians for a listing based on its zone.
     */
    public function suggestForListing(Request $request, PropertyListing $listing): JsonResponse
    {
        $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:technician_categories,id'],
        ]);

        if (! $listing->zone_id) {
            return $this->success([], 'No zone associated with this listing.');
        }

        $zone = Zone::find($listing->zone_id);

        if (! $zone) {
            return $this->success([]);
        }

        $query = Technician::with(['user', 'category', 'zone'])
            ->availableForZone($zone);

        if ($request->filled('category_id')) {
            $query->where('technician_category_id', $request->integer('category_id'));
        }

        $technicians = $query->orderByDesc('rating')->limit(10)->get();

        return $this->success(TechnicianResource::collection($technicians));
    }
}
