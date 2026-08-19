<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\AgreementTemplate;
use App\Models\PropertyListing;
use App\Models\Tenancy;
use App\Models\Unit;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenancyController extends Controller
{
    use GatesRentManagementFeatures;

    public function create(Request $request, PostEntitlementService $entitlement): View|RedirectResponse
    {
        $features = $entitlement->unlockedFeatures($request->user());

        if (! $this->hasRentManagementAccess($features)) {
            return redirect()->route('owner.rent-management.index')
                ->withErrors(['limit' => 'Rent management requires an active subscription with one of its features.']);
        }

        return view('website.owner.rent-management.tenancies.form', [
            'tenancy' => null,
            'listings' => $this->listings($request),
            'features' => $features,
        ]);
    }

    public function store(Request $request, PostEntitlementService $entitlement): RedirectResponse
    {
        $user = $request->user();
        $features = $entitlement->unlockedFeatures($user);

        if (! $this->hasRentManagementAccess($features)) {
            return back()->withErrors(['limit' => 'Rent management requires an active subscription with one of its features.']);
        }

        $validated = $request->validate([
            'property_listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'tenant_name' => ['nullable', 'string', 'max:255'],
            'tenant_phone' => ['nullable', 'string', 'max:30'],
            'tenant_email' => ['nullable', 'email', 'max:255'],
            'rent_amount' => ['required', 'integer', 'min:0'],
            'deposit_amount' => ['nullable', 'integer', 'min:0'],
            'due_day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        PropertyListing::where('id', $validated['property_listing_id'])->where('owner_id', $user->id)->firstOrFail();

        if (! empty($validated['unit_id'])) {
            if (! $this->hasFeature($features, 'multi_unit')) {
                return back()->withErrors(['unit_id' => 'Assigning tenancies to individual units requires the multi-unit feature.'])->withInput();
            }

            Unit::where('id', $validated['unit_id'])->where('property_listing_id', $validated['property_listing_id'])->firstOrFail();
        }

        $tenancy = Tenancy::create([
            'owner_id' => $user->id,
            'status' => 'active',
            ...$validated,
        ]);

        return redirect()->route('owner.rent-management.tenancies.show', $tenancy)->with('success', 'Tenancy added.');
    }

    public function show(Request $request, Tenancy $tenancy): View
    {
        $this->authorize('view', $tenancy);

        $tenancy->load(['listing:id,title', 'unit:id,name', 'tenant:id,name']);
        $rentPayments = $tenancy->rentPayments()->with('receipt')->orderByDesc('due_date')->get();
        $ledgerTransactions = $tenancy->ledgerTransactions()->orderByDesc('occurred_on')->limit(20)->get();
        $agreements = $tenancy->agreements()->orderByDesc('generated_at')->get();
        $templates = AgreementTemplate::where('owner_id', $request->user()->id)->get();

        return view('website.owner.rent-management.tenancies.show', compact('tenancy', 'rentPayments', 'ledgerTransactions', 'agreements', 'templates'));
    }

    public function edit(Tenancy $tenancy, Request $request): View
    {
        $this->authorize('update', $tenancy);

        return view('website.owner.rent-management.tenancies.form', [
            'tenancy' => $tenancy,
            'listings' => $this->listings($request),
        ]);
    }

    public function update(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $this->authorize('update', $tenancy);

        $validated = $request->validate([
            'rent_amount' => ['required', 'integer', 'min:0'],
            'deposit_amount' => ['nullable', 'integer', 'min:0'],
            'due_day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'ended', 'terminated'])],
        ]);

        $tenancy->update($validated);

        return redirect()->route('owner.rent-management.tenancies.show', $tenancy)->with('success', 'Tenancy updated.');
    }

    public function destroy(Tenancy $tenancy): RedirectResponse
    {
        $this->authorize('delete', $tenancy);
        $tenancy->delete();

        return redirect()->route('owner.rent-management.index')->with('success', 'Tenancy removed.');
    }

    /** @return Collection<int, PropertyListing> */
    private function listings(Request $request): Collection
    {
        return PropertyListing::where('owner_id', $request->user()->id)->orderBy('title')->get(['id', 'title']);
    }
}
