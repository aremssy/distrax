<?php

namespace App\Http\Controllers\Api\V1\Engagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Engagement\StoreSavedSearchRequest;
use App\Http\Requests\Api\V1\Engagement\UpdateSavedSearchRequest;
use App\Http\Resources\Api\V1\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchController extends ApiController
{
    /**
     * GET /api/v1/saved-searches
     */
    public function index(Request $request): JsonResponse
    {
        $searches = SavedSearch::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->success(SavedSearchResource::collection($searches));
    }

    /**
     * POST /api/v1/saved-searches
     *
     * Stores the supplied filter criteria as a saved search.
     * Up to 20 saved searches per user.
     */
    public function store(StoreSavedSearchRequest $request): JsonResponse
    {
        $user = $request->user();

        $count = SavedSearch::where('user_id', $user->id)->count();

        if ($count >= 20) {
            return $this->error('You can save up to 20 searches. Please delete one before adding another.', 422);
        }

        $search = SavedSearch::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'criteria' => $request->input('criteria'),
            'alert_on' => $request->boolean('alert_on', false),
            'is_mandate' => $request->boolean('is_mandate', false),
            'min_discount_pct' => $request->input('min_discount_pct'),
            'min_deal_score' => $request->input('min_deal_score'),
            'frequency' => $request->input('frequency'),
        ]);

        return $this->created(new SavedSearchResource($search), 'Search saved.');
    }

    /**
     * PUT /api/v1/saved-searches/{savedSearch}
     */
    public function update(UpdateSavedSearchRequest $request, SavedSearch $savedSearch): JsonResponse
    {
        $this->authorizeOwnership($request, $savedSearch);

        $savedSearch->update(array_filter([
            'name' => $request->input('name', $savedSearch->name),
            'criteria' => $request->input('criteria', $savedSearch->criteria),
            'alert_on' => $request->has('alert_on') ? $request->boolean('alert_on') : $savedSearch->alert_on,
            'is_mandate' => $request->has('is_mandate') ? $request->boolean('is_mandate') : $savedSearch->is_mandate,
            'min_discount_pct' => $request->input('min_discount_pct', $savedSearch->min_discount_pct),
            'min_deal_score' => $request->input('min_deal_score', $savedSearch->min_deal_score),
            'frequency' => $request->input('frequency', $savedSearch->frequency),
        ], fn ($v) => $v !== null));

        return $this->success(new SavedSearchResource($savedSearch->fresh()), 'Saved search updated.');
    }

    /**
     * DELETE /api/v1/saved-searches/{savedSearch}
     */
    public function destroy(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        $this->authorizeOwnership($request, $savedSearch);

        $savedSearch->delete();

        return $this->success(null, 'Saved search deleted.');
    }

    private function authorizeOwnership(Request $request, SavedSearch $search): void
    {
        if ($search->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this saved search.');
        }
    }
}
