<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Technician\StoreMaintenanceRequestRequest;
use App\Http\Requests\Api\V1\Technician\UpdateMaintenanceRequestRequest;
use App\Http\Resources\Api\V1\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Models\PropertyListing;
use App\Services\MaintenanceRequestService;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends ApiController
{
    /**
     * POST /api/v1/maintenance-requests
     *
     * Tenant submits a maintenance request for a property.
     * Gated behind the 'maintenance_requests' subscription feature.
     */
    public function store(StoreMaintenanceRequestRequest $request, PostEntitlementService $entitlement, MaintenanceRequestService $maintenance): JsonResponse
    {
        $user = $request->user();

        $features = $entitlement->unlockedFeatures($user);

        if (! in_array('maintenance_requests', $features, true)) {
            return $this->error('Submitting maintenance requests requires an active subscription.', 403);
        }

        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));

        if (! $maintenance->canSubmitFor($user, $listing)) {
            return $this->error('You can only submit maintenance requests for a listing you own or have booked.', 403);
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'tenant_id' => $user->id,
            'owner_id' => $listing->owner_id,
            'property_listing_id' => $listing->id,
            'technician_id' => $request->input('technician_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'normal'),
            'status' => 'open',
        ]);

        $maintenanceRequest->load(['listing', 'technician.user', 'tenant', 'owner']);

        return $this->created(new MaintenanceRequestResource($maintenanceRequest), 'Maintenance request submitted.');
    }

    /**
     * GET /api/v1/maintenance-requests
     *
     * List maintenance requests. role=tenant (default) or role=owner.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['sometimes', Rule::in(['tenant', 'owner'])],
            'status' => ['sometimes', Rule::in(['open', 'assigned', 'in_progress', 'completed', 'closed'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $role = $request->input('role', 'tenant');
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = MaintenanceRequest::with(['listing', 'technician.user', 'tenant', 'owner'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at');

        if ($role === 'owner') {
            $query->where('owner_id', $user->id);
        } else {
            $query->where('tenant_id', $user->id);
        }

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator, MaintenanceRequestResource::class);
    }

    /**
     * PATCH /api/v1/maintenance-requests/{maintenanceRequest}
     *
     * Advances the request through the tenant → owner → technician loop:
     *  - owner:      open        → assigned     (picks a technician)
     *  - technician: assigned    → in_progress  (starts the job)
     *  - technician: in_progress → completed    (marks the job done)
     *  - tenant:     completed   → closed        (confirms and closes)
     */
    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest, MaintenanceRequestService $maintenance): JsonResponse
    {
        $result = $maintenance->transition(
            $maintenanceRequest,
            $request->user(),
            $request->string('status')->value(),
            $request->integer('technician_id') ?: null,
        );

        if (! $result['ok']) {
            return $this->error($result['message'], $result['status_code']);
        }

        $maintenanceRequest->load(['listing', 'technician.user', 'tenant', 'owner']);

        return $this->success(new MaintenanceRequestResource($maintenanceRequest), $result['message']);
    }
}
