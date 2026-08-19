<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStoreZonesRequest;
use App\Http\Requests\Admin\ImportZonesRequest;
use App\Http\Requests\Admin\SaveZoneRequest;
use App\Models\Zone;
use App\Services\ZoneCsvExporter;
use App\Services\ZoneCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ZoneController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search');
        $type = $request->string('type');

        if ($search->isNotEmpty() || $type->isNotEmpty()) {
            $query = Zone::with('parent')->orderBy('type')->orderBy('name');

            if ($search->isNotEmpty()) {
                $query->where('name', 'like', "%{$search}%");
            }
            if ($type->isNotEmpty()) {
                $query->where('type', $type->value());
            }

            $zones = $query->paginate(50)->withQueryString();
            $tree = null;
        } else {
            $tree = Zone::roots()
                ->with('allChildren')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $zones = null;
        }

        $counts = Zone::selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $parents = Zone::whereIn('type', ['country', 'state', 'city'])
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.zones.index', compact('tree', 'zones', 'search', 'type', 'counts', 'parents'));
    }

    public function create(Request $request): View
    {
        $parents = Zone::whereIn('type', ['country', 'state', 'city'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $parent = $request->integer('parent_id')
            ? Zone::find($request->integer('parent_id'))
            : null;

        return view('admin.zones.create', compact('parents', 'parent'));
    }

    public function store(SaveZoneRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['boundary'] = $this->decodeBoundary($validated['boundary'] ?? null);

        Zone::create($validated);

        return redirect()->route('admin.zones.index')->with('success', 'Zone added.');
    }

    public function edit(Zone $zone): View
    {
        $parents = Zone::whereIn('type', ['country', 'state', 'city'])
            ->where('id', '!=', $zone->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.zones.edit', compact('zone', 'parents'));
    }

    public function update(SaveZoneRequest $request, Zone $zone): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['parent_id'])) {
            $forbidden = [$zone->id, ...$zone->descendantIds()];
            if (in_array((int) $validated['parent_id'], $forbidden)) {
                return back()
                    ->withErrors(['parent_id' => 'A zone cannot be set as a descendant of itself.'])
                    ->withInput();
            }
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['boundary'] = $this->decodeBoundary($validated['boundary'] ?? null);

        $zone->update($validated);

        return redirect()->route('admin.zones.index')->with('success', 'Zone updated.');
    }

    /**
     * The boundary arrives as a JSON string (a GeoJSON-style array of [lat, lng] pairs
     * drawn on the admin map) and the model casts `boundary` to `array`, so it must be
     * decoded here before assignment — passing the raw string through would double-encode it.
     *
     * @return array<int, array{0: float, 1: float}>|null
     */
    private function decodeBoundary(?string $boundary): ?array
    {
        if (! $boundary) {
            return null;
        }

        $decoded = json_decode($boundary, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        if ($zone->children()->exists()) {
            return back()->with('error', 'Cannot delete a zone that has child zones. Remove the children first.');
        }

        // property_listings.zone_id is restrictOnDelete — deleting anyway would be a 500.
        if ($zone->listings()->withTrashed()->exists()) {
            return back()->with('error', 'Cannot delete a zone that has listings. Move or delete its listings first.');
        }

        $zone->delete();

        return redirect()->route('admin.zones.index')->with('success', 'Zone deleted.');
    }

    public function bulkStore(BulkStoreZonesRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $parent = Zone::findOrFail($validated['parent_id']);
        $lines = array_filter(array_map('trim', explode("\n", $validated['names'])));

        // One paste = one batch. A row failing midway would otherwise commit the
        // zones before it while the admin is told nothing (or the wrong count) landed.
        $created = DB::transaction(function () use ($lines, $parent, $validated): int {
            $created = 0;

            foreach ($lines as $name) {
                Zone::create([
                    'name' => $name,
                    'type' => $validated['type'],
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
                $created++;
            }

            return $created;
        });

        return redirect()->route('admin.zones.index')
            ->with('success', "{$created} zone(s) added under {$parent->name}.");
    }

    public function import(ImportZonesRequest $request, ZoneCsvImporter $importer): RedirectResponse
    {
        ['created' => $created, 'skipped' => $skipped] = $importer->import($request->file('csv_file'));

        $msg = "Imported {$created} zone(s)".($skipped ? ", skipped {$skipped} empty row(s)." : '.');

        return redirect()->route('admin.zones.index')->with('success', $msg);
    }

    public function export(ZoneCsvExporter $exporter): StreamedResponse
    {
        return $exporter->export();
    }
}
