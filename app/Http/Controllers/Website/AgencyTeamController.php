<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\ImportAgencyListingsRequest;
use App\Http\Requests\Website\StoreAgencyRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use App\Models\Zone;
use App\Services\PropertyListingCsvImporter;
use App\Services\UniqueSlug;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgencyTeamController extends Controller
{
    /**
     * Self-service agent management for the agency the authenticated user owns.
     */
    public function index(Request $request): View
    {
        $agency = $this->ownedAgency($request);

        $agency?->load(['agents.user:id,name,email', 'zone:id,name']);

        $zones = $agency ? collect() : Zone::active()->orderBy('name')->get(['id', 'name']);

        return view('website.owner.agency.index', compact('agency', 'zones'));
    }

    public function storeAgency(StoreAgencyRequest $request): RedirectResponse
    {
        if ($this->ownedAgency($request) !== null) {
            return back()->withErrors(['name' => 'You already manage an agency.']);
        }

        $validated = $request->validated();

        Agency::create([
            'owner_id' => $request->user()->id,
            'zone_id' => $validated['zone_id'] ?? null,
            'name' => $validated['name'],
            'slug' => UniqueSlug::make(Agency::class, $validated['name'], 'agency'),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo' => $request->file('logo')?->store('agencies', 'public'),
            'is_verified' => false,
            'status' => 'active',
        ]);

        return redirect()->route('owner.agency.index')->with('success', 'Agency created. You can now add agents and import listings.');
    }

    public function store(Request $request): RedirectResponse
    {
        $agency = $this->requireOwnedAgency($request);

        $validated = $request->validate([
            'user_email' => ['required', 'email', 'exists:users,email'],
            'title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::where('email', $validated['user_email'])->firstOrFail();

        if ($agency->agents()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['user_email' => 'This user is already an agent of your agency.']);
        }

        $agency->agents()->create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Agent added to your team.');
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $agency = $this->requireOwnedAgency($request);
        abort_unless($agent->agency_id === $agency->id, 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $agent->update($validated);

        return back()->with('success', 'Agent updated.');
    }

    public function destroy(Request $request, Agent $agent): RedirectResponse
    {
        $agency = $this->requireOwnedAgency($request);
        abort_unless($agent->agency_id === $agency->id, 403);

        $agent->delete();

        return back()->with('success', 'Agent removed from your team.');
    }

    public function importListings(ImportAgencyListingsRequest $request, PropertyListingCsvImporter $importer): RedirectResponse
    {
        $agency = $this->requireOwnedAgency($request);

        $result = $importer->import($request->file('csv_file'), $agency, $request->user());

        return back()->with(
            'success',
            "Imported {$result['created']} listing(s). {$result['skipped']} row(s) were skipped."
        );
    }

    private function ownedAgency(Request $request): ?Agency
    {
        return Agency::where('owner_id', $request->user()->id)->first();
    }

    private function requireOwnedAgency(Request $request): Agency
    {
        $agency = $this->ownedAgency($request);

        abort_if($agency === null, 403, 'You do not own an agency.');

        return $agency;
    }
}
