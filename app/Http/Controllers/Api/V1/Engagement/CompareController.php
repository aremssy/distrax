<?php

namespace App\Http\Controllers\Api\V1\Engagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Compare;
use App\Models\PropertyListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends ApiController
{
    /** Maximum listings allowed in a single compare session. */
    private const MAX_ITEMS = 5;

    /**
     * GET /api/v1/compare?ids=1,2,3
     *
     * Public endpoint. Returns listings side-by-side with key attributes
     * for rendering a comparison table on the website or app.
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $ids = array_slice(
            array_filter(array_map('intval', explode(',', $request->input('ids')))),
            0,
            self::MAX_ITEMS
        );

        if (empty($ids)) {
            return $this->error('No valid listing IDs supplied.', 422);
        }

        $listings = PropertyListing::whereIn('id', $ids)
            ->where('status', 'active')
            ->with(['zone', 'images'])
            ->get()
            ->keyBy('id');

        // Preserve the requested order
        $ordered = collect($ids)
            ->filter(fn ($id) => $listings->has($id))
            ->map(fn ($id) => $this->compareCard($listings[$id]))
            ->values();

        return $this->success([
            'count' => $ordered->count(),
            'listings' => $ordered,
        ]);
    }

    /**
     * GET /api/v1/compare/my
     *
     * Returns the authenticated user's persisted compare set.
     */
    public function mySet(Request $request): JsonResponse
    {
        $user = $request->user();

        $listings = PropertyListing::query()
            ->join('compares', 'compares.property_listing_id', '=', 'property_listings.id')
            ->where('compares.user_id', $user->id)
            ->with(['zone', 'images'])
            ->select('property_listings.*')
            ->orderByDesc('compares.created_at')
            ->get();

        return $this->success([
            'count' => $listings->count(),
            'listings' => $listings->map(fn ($l) => $this->compareCard($l))->values(),
        ]);
    }

    /**
     * POST /api/v1/compare/{listing}
     *
     * Add a listing to the authenticated user's compare set (max 5).
     */
    public function add(Request $request, PropertyListing $listing): JsonResponse
    {
        $user = $request->user();

        $currentCount = Compare::where('user_id', $user->id)->count();

        if ($currentCount >= self::MAX_ITEMS) {
            return $this->error('You can compare up to '.self::MAX_ITEMS.' listings at a time. Remove one to add another.', 422);
        }

        $exists = Compare::where('user_id', $user->id)
            ->where('property_listing_id', $listing->id)
            ->exists();

        if ($exists) {
            return $this->error('Listing is already in your compare set.', 409);
        }

        Compare::create([
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
        ]);

        return $this->success(
            ['count' => Compare::where('user_id', $user->id)->count()],
            'Added to compare set.'
        );
    }

    /**
     * DELETE /api/v1/compare/{listing}
     *
     * Remove a single listing from the authenticated user's compare set.
     */
    public function remove(Request $request, PropertyListing $listing): JsonResponse
    {
        $deleted = Compare::where('user_id', $request->user()->id)
            ->where('property_listing_id', $listing->id)
            ->delete();

        if (! $deleted) {
            return $this->error('Listing is not in your compare set.', 404);
        }

        return $this->success(
            ['count' => Compare::where('user_id', $request->user()->id)->count()],
            'Removed from compare set.'
        );
    }

    /**
     * DELETE /api/v1/compare
     *
     * Clear the entire compare set for the authenticated user.
     */
    public function clear(Request $request): JsonResponse
    {
        Compare::where('user_id', $request->user()->id)->delete();

        return $this->success(null, 'Compare set cleared.');
    }

    /**
     * Build the side-by-side comparison card for a single listing.
     *
     * @return array<string, mixed>
     */
    private function compareCard(PropertyListing $listing): array
    {
        $cover = $listing->relationLoaded('images')
            ? ($listing->images->firstWhere('is_cover', true) ?? $listing->images->first())
            : null;

        return [
            'id' => $listing->id,
            'title' => $listing->title,
            'type' => $listing->type,
            'cover_image' => $cover ? asset('storage/'.$cover->path) : null,
            'zone' => $listing->relationLoaded('zone') ? [
                'id' => $listing->zone?->id,
                'name' => $listing->zone?->name,
            ] : null,
            'address' => $listing->address,
            'price' => (int) $listing->price,
            'service_charge' => (int) $listing->service_charge,
            'advance_months' => $listing->advance_months,
            'bedrooms' => $listing->bedrooms,
            'bathrooms' => $listing->bathrooms,
            'area_sqft' => $listing->area_sqft,
            'floor' => $listing->floor,
            'total_floors' => $listing->total_floors,
            'furnished' => $listing->furnished,
            'parking' => $listing->parking,
            'allowed_for' => $listing->allowed_for,
            'utility_flags' => $listing->utility_flags ?? [],
            'is_featured' => $listing->is_featured,
            'is_verified' => $listing->is_verified,
            'lat' => $listing->lat !== null ? (float) $listing->lat : null,
            'lng' => $listing->lng !== null ? (float) $listing->lng : null,
        ];
    }
}
