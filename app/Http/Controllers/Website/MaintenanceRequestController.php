<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\StoreMaintenanceRequestRequest;
use App\Http\Requests\Api\V1\Technician\UpdateMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Models\PropertyListing;
use App\Models\Technician;
use App\Services\MaintenanceRequestService;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tenant/owner side of maintenance requests. The technician side lives in
 * TechnicianDashboardController, which shares the same MaintenanceRequestService
 * transition rules so both surfaces agree on what moves are legal.
 */
class MaintenanceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'role' => ['sometimes', Rule::in(['tenant', 'owner'])],
        ]);

        $user = $request->user();
        $role = $request->input('role', 'tenant');

        $requests = MaintenanceRequest::with(['listing:id,title,slug', 'technician.user:id,name', 'tenant:id,name', 'owner:id,name'])
            ->where($role === 'owner' ? 'owner_id' : 'tenant_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $technicians = $role === 'owner'
            ? Technician::with('user:id,name')->where('status', 'active')->get(['id', 'user_id'])
            : collect();

        return view('website.dashboard.maintenance.index', compact('requests', 'role', 'technicians'));
    }

    public function create(Request $request, MaintenanceRequestService $maintenance): View
    {
        $listings = $maintenance->eligibleListingsFor($request->user());

        return view('website.dashboard.maintenance.create', compact('listings'));
    }

    public function store(StoreMaintenanceRequestRequest $request, PostEntitlementService $entitlement, MaintenanceRequestService $maintenance): RedirectResponse
    {
        $user = $request->user();

        if (! in_array('maintenance_requests', $entitlement->unlockedFeatures($user), true)) {
            return back()->withErrors(['feature' => 'Submitting maintenance requests requires an active subscription.']);
        }

        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));

        if (! $maintenance->canSubmitFor($user, $listing)) {
            return back()->withErrors(['property_listing_id' => 'You can only submit maintenance requests for a listing you own or have booked.']);
        }

        MaintenanceRequest::create([
            'tenant_id' => $user->id,
            'owner_id' => $listing->owner_id,
            'property_listing_id' => $listing->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'normal'),
            'status' => 'open',
        ]);

        return redirect()->route('dashboard.maintenance.index')->with('success', 'Maintenance request submitted.');
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest, MaintenanceRequestService $maintenance): RedirectResponse
    {
        $result = $maintenance->transition(
            $maintenanceRequest,
            $request->user(),
            $request->string('status')->value(),
            $request->integer('technician_id') ?: null,
        );

        if (! $result['ok']) {
            return back()->withErrors(['status' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}
