<?php

namespace App\Http\Controllers\Api\V1\Visit;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Visit\StoreVisitRequest;
use App\Http\Requests\Api\V1\Visit\UpdateVisitRequest;
use App\Http\Resources\Api\V1\VisitResource;
use App\Models\PropertyListing;
use App\Models\VisitSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitController extends ApiController
{
    /**
     * POST /api/v1/listings/{listing}/visits
     *
     * Request a visit for a listing.
     */
    public function store(StoreVisitRequest $request, PropertyListing $listing): JsonResponse
    {
        if ($listing->status !== 'active') {
            return $this->error('This listing is not available for visits.', 422);
        }

        if ($listing->owner_id === $request->user()->id) {
            return $this->error('You cannot schedule a visit to your own listing.', 422);
        }

        $visit = VisitSchedule::create([
            'property_listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'scheduled_at' => $request->input('scheduled_at'),
            'note' => $request->input('note'),
            'status' => 'pending',
        ]);

        $visit->load(['listing', 'user']);

        return $this->created(new VisitResource($visit), 'Visit request submitted.');
    }

    /**
     * GET /api/v1/visits
     *
     * List visits for the authenticated user.
     * Requesters see visits they made; listing owners see incoming requests.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['sometimes', Rule::in(['requester', 'owner'])],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $role = $request->input('role', 'requester');
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = VisitSchedule::query()->with(['listing', 'user']);

        if ($role === 'owner') {
            // Owner sees incoming visits for their listings
            $query->whereHas('listing', fn ($q) => $q->where('owner_id', $user->id));
        } else {
            $query->where('user_id', $user->id);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('scheduled_at')->paginate($perPage);

        return $this->paginated($paginator, VisitResource::class);
    }

    /**
     * PATCH /api/v1/visits/{visit}
     *
     * Confirm or cancel a visit.
     *   - Requester: can cancel their own pending visit.
     *   - Listing owner: can confirm or cancel any pending visit.
     */
    public function update(UpdateVisitRequest $request, VisitSchedule $visit): JsonResponse
    {
        $user = $request->user();
        $newStatus = $request->validated('status');
        $isOwner = $visit->listing->owner_id === $user->id;
        $isRequester = $visit->user_id === $user->id;

        if (! $isOwner && ! $isRequester) {
            return $this->error('You do not have permission to update this visit.', 403);
        }

        if ($visit->status !== 'pending') {
            return $this->error('Only pending visits can be updated.', 422);
        }

        // Requesters can only cancel; owners can confirm or cancel
        if ($isRequester && ! $isOwner && $newStatus !== 'cancelled') {
            return $this->error('You can only cancel your own visit request.', 403);
        }

        $visit->update([
            'status' => $newStatus,
            'note' => $request->input('note', $visit->note),
        ]);

        return $this->success(new VisitResource($visit->load(['listing', 'user'])), 'Visit updated.');
    }
}
