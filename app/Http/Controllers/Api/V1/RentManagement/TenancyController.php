<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\RentManagement\StoreTenancyRequest;
use App\Http\Requests\Api\V1\RentManagement\UpdateTenancyRequest;
use App\Http\Resources\Api\V1\RentManagement\TenancyResource;
use App\Models\PropertyListing;
use App\Models\Tenancy;
use App\Models\Unit;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenancyController extends ApiController
{
    use GatesRentManagementFeatures;

    /**
     * GET /api/v1/rent-management/tenancies
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(['active', 'ended', 'terminated'])],
            'property_listing_id' => ['sometimes', 'integer', 'exists:property_listings,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $tenancies = Tenancy::where('owner_id', $request->user()->id)
            ->with('tenant:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('property_listing_id'), fn ($q) => $q->where('property_listing_id', $request->integer('property_listing_id')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginated($tenancies, TenancyResource::class);
    }

    public function store(StoreTenancyRequest $request, PostEntitlementService $entitlement): JsonResponse
    {
        $user = $request->user();
        $this->authorize('create', Tenancy::class);

        $features = $entitlement->unlockedFeatures($user);

        if (! $this->hasRentManagementAccess($features)) {
            return $this->error('Rent management requires an active subscription.', 403);
        }

        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));
        $this->authorize('update', $listing);

        if ($request->filled('unit_id')) {
            if ($response = $this->featureGate($features, 'multi_unit', 'Assigning tenancies to individual units requires the multi-unit feature.')) {
                return $response;
            }

            Unit::where('id', $request->integer('unit_id'))
                ->where('property_listing_id', $listing->id)
                ->firstOrFail();
        }

        $tenancy = Tenancy::create([
            'owner_id' => $user->id,
            ...$request->validated(),
        ]);

        return $this->created(new TenancyResource($tenancy->load('tenant:id,name')), 'Tenancy created.');
    }

    public function show(Tenancy $tenancy): JsonResponse
    {
        $this->authorize('view', $tenancy);

        return $this->success(new TenancyResource($tenancy->load('tenant:id,name')));
    }

    public function update(UpdateTenancyRequest $request, Tenancy $tenancy): JsonResponse
    {
        $this->authorize('update', $tenancy);

        $tenancy->update($request->validated());

        return $this->success(new TenancyResource($tenancy->load('tenant:id,name')));
    }

    public function destroy(Tenancy $tenancy): JsonResponse
    {
        $this->authorize('delete', $tenancy);

        $tenancy->delete();

        return $this->success(null, 'Tenancy removed.');
    }
}
