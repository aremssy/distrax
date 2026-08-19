<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveListingPackageRequest;
use App\Models\ListingPackage;
use App\Services\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingPackageController extends Controller
{
    use ReordersRecords;

    public function index()
    {
        return view('admin.packages.index', [
            'packages' => ListingPackage::orderBy('sort_order')->orderBy('price')->paginate(25),
        ]);
    }

    public function store(SaveListingPackageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = UniqueSlug::make(ListingPackage::class, $data['name'], 'package');
        $data['sort_order'] = $this->nextSortOrder(ListingPackage::class);

        ListingPackage::create($data);

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function show(ListingPackage $package): RedirectResponse
    {
        return redirect()->route('admin.packages.index');
    }

    public function update(SaveListingPackageRequest $request, ListingPackage $package): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = UniqueSlug::make(ListingPackage::class, $data['name'], 'package', $package->id);

        $package->update($data);

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(ListingPackage $package): RedirectResponse
    {
        if ($package->payments()->exists()) {
            return redirect()->route('admin.packages.index')->with('error', 'Packages with payments cannot be deleted. Deactivate instead.');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted.');
    }

    public function toggle(ListingPackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        return redirect()->route('admin.packages.index')->with('success', 'Package status toggled.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:listing_packages,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(ListingPackage::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
