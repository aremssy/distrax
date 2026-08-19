<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\PropertyListing;
use App\Models\Unit;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    use GatesRentManagementFeatures;

    public function index(Request $request, PropertyListing $listing, PostEntitlementService $entitlement): View|RedirectResponse
    {
        $this->authorize('update', $listing);

        if (! $this->hasFeature($entitlement->unlockedFeatures($request->user()), 'multi_unit')) {
            return redirect()->route('owner.rent-management.index')
                ->withErrors(['limit' => 'Multi-unit management requires an active subscription with that feature.']);
        }

        $units = Unit::where('property_listing_id', $listing->id)->orderBy('name')->get();

        return view('website.owner.rent-management.units.index', compact('listing', 'units'));
    }

    public function store(Request $request, PropertyListing $listing, PostEntitlementService $entitlement): RedirectResponse
    {
        $this->authorize('update', $listing);

        if (! $this->hasFeature($entitlement->unlockedFeatures($request->user()), 'multi_unit')) {
            return back()->withErrors(['limit' => 'Multi-unit management requires an active subscription with that feature.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'rent_amount' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['vacant', 'occupied', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Unit::create(['property_listing_id' => $listing->id, ...$validated]);

        return back()->with('success', 'Unit added.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit->listing);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'rent_amount' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['vacant', 'occupied', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $unit->update($validated);

        return back()->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit->listing);
        $unit->delete();

        return back()->with('success', 'Unit removed.');
    }
}
