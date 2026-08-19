<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\RentManagement\StoreUnitRequest;
use App\Http\Requests\Api\V1\RentManagement\UpdateUnitRequest;
use App\Http\Resources\Api\V1\RentManagement\UnitResource;
use App\Models\PropertyListing;
use App\Models\Unit;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends ApiController
{
    use GatesRentManagementFeatures;

    /**
     * GET /api/v1/rent-management/units?property_listing_id=
     */
    public function index(Request $request, PostEntitlementService $entitlement): JsonResponse
    {
        $request->validate([
            'property_listing_id' => ['required', 'integer', 'exists:property_listings,id'],
        ]);

        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));
        $this->authorize('update', $listing);

        if ($response = $this->featureGate($entitlement->unlockedFeatures($request->user()), 'multi_unit', 'Multi-unit management requires an active subscription with that feature.')) {
            return $response;
        }

        $units = Unit::where('property_listing_id', $listing->id)->orderBy('name')->get();

        return $this->success(UnitResource::collection($units));
    }

    public function store(StoreUnitRequest $request, PostEntitlementService $entitlement): JsonResponse
    {
        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));
        $this->authorize('update', $listing);

        if ($response = $this->featureGate($entitlement->unlockedFeatures($request->user()), 'multi_unit', 'Multi-unit management requires an active subscription with that feature.')) {
            return $response;
        }

        $unit = Unit::create($request->validated());

        return $this->created(new UnitResource($unit), 'Unit created.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit->listing);

        $unit->update($request->validated());

        return $this->success(new UnitResource($unit));
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit->listing);

        $unit->delete();

        return $this->success(null, 'Unit deleted.');
    }
}
