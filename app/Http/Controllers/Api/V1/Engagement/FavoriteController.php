<?php

namespace App\Http\Controllers\Api\V1\Engagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Favorite;
use App\Models\PropertyListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends ApiController
{
    /**
     * GET /api/v1/favorites
     *
     * Paginated list of the authenticated user's favorited listings.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $paginator = PropertyListing::query()
            ->join('favorites', 'favorites.property_listing_id', '=', 'property_listings.id')
            ->where('favorites.user_id', $request->user()->id)
            ->whereIn('property_listings.status', ['active', 'rented', 'sold'])
            ->withCount(['reviews', 'favorites'])
            ->with(['zone', 'owner', 'images'])
            ->select('property_listings.*', 'favorites.created_at as favorited_at')
            ->orderByDesc('favorites.created_at')
            ->paginate($perPage);

        return $this->paginated($paginator, ListingResource::class);
    }

    /**
     * POST /api/v1/favorites/{listing}
     *
     * Add a listing to favorites. Returns 409 if already favorited.
     */
    public function store(Request $request, PropertyListing $listing): JsonResponse
    {
        $user = $request->user();

        $exists = Favorite::where('user_id', $user->id)
            ->where('property_listing_id', $listing->id)
            ->exists();

        if ($exists) {
            return $this->error('Listing is already in your favorites.', 409);
        }

        Favorite::create([
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
        ]);

        return $this->success(
            ['favorites_count' => Favorite::where('property_listing_id', $listing->id)->count()],
            'Added to favorites.'
        );
    }

    /**
     * DELETE /api/v1/favorites/{listing}
     *
     * Remove a listing from favorites.
     */
    public function destroy(Request $request, PropertyListing $listing): JsonResponse
    {
        $deleted = Favorite::where('user_id', $request->user()->id)
            ->where('property_listing_id', $listing->id)
            ->delete();

        if (! $deleted) {
            return $this->error('Listing is not in your favorites.', 404);
        }

        return $this->success(
            ['favorites_count' => Favorite::where('property_listing_id', $listing->id)->count()],
            'Removed from favorites.'
        );
    }
}
