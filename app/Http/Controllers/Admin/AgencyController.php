<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportAgenciesRequest;
use App\Http\Requests\Admin\StoreAgencyRequest;
use App\Http\Requests\Admin\UpdateAgencyRequest;
use App\Models\Agency;
use App\Models\User;
use App\Models\Zone;
use App\Services\AgencyCsvExporter;
use App\Services\AgencyCsvImporter;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgencyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search');

        $agencies = Agency::with('owner:id,name,email')
            ->withCount(['agents', 'listings'])
            ->when($search->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.agencies.index', compact('agencies'));
    }

    public function create(): View
    {
        $zones = Zone::active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name']);

        return view('admin.agencies.create', compact('zones'));
    }

    public function store(StoreAgencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $owner = User::where('email', $validated['owner_email'])->firstOrFail();

        Agency::create([
            'owner_id' => $owner->id,
            'zone_id' => $validated['zone_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo' => $request->file('logo')?->store('agencies', 'public'),
            'is_verified' => false,
            'status' => 'active',
        ]);

        return redirect()->route('admin.agencies.index')->with('success', 'Agency created.');
    }

    public function show(Agency $agency): View
    {
        $agency->load([
            'owner:id,name,email,phone',
            'zone:id,name',
            'agents.user:id,name,email,phone',
            'listings' => fn ($q) => $q->with('zone:id,name')->latest()->limit(20),
        ])->loadCount('listings');

        return view('admin.agencies.show', compact('agency'));
    }

    public function edit(Agency $agency): View
    {
        $agency->load('owner:id,name,email');

        return view('admin.agencies.edit', compact('agency'));
    }

    public function update(UpdateAgencyRequest $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_verified'] = $request->boolean('is_verified');

        if ($request->hasFile('logo')) {
            $oldLogo = $agency->logo;
            $validated['logo'] = $request->file('logo')->store('agencies', 'public');
        } else {
            unset($validated['logo']);
        }

        $agency->update($validated);

        if (isset($oldLogo) && $oldLogo && ! str_starts_with($oldLogo, 'http')) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('admin.agencies.index')->with('success', 'Agency updated.');
    }

    public function destroy(Agency $agency): RedirectResponse
    {
        $agency->delete();

        return redirect()->route('admin.agencies.index')->with('success', 'Agency deleted.');
    }

    public function restore(Agency $agency): RedirectResponse
    {
        $agency->restore();

        return redirect()->route('admin.agencies.index')->with('success', 'Agency restored.');
    }

    public function import(ImportAgenciesRequest $request, AgencyCsvImporter $importer): RedirectResponse
    {
        ['created' => $created, 'skipped' => $skipped] = $importer->import($request->file('csv_file'));

        $msg = "Imported {$created} agenc(y/ies)".($skipped ? ", skipped {$skipped} row(s) missing a name or a matching owner_email." : '.');

        return redirect()->route('admin.agencies.index')->with('success', $msg);
    }

    public function export(AgencyCsvExporter $exporter): StreamedResponse
    {
        return $exporter->export();
    }

    private function uniqueSlug(string $name): string
    {
        // UniqueSlug checks without global scopes, so soft-deleted agencies
        // (which still occupy the DB unique index) count as collisions.
        return UniqueSlug::make(Agency::class, $name, 'agency');
    }
}
