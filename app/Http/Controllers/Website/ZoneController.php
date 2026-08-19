<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AreaLandingPage;
use App\Models\PropertyListing;
use App\Models\Zone;
use App\Services\SchemaMarkupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ZoneController extends Controller
{
    public function landing(Zone $zone, SchemaMarkupService $schemaMarkup): View
    {
        $landingPage = AreaLandingPage::where('zone_id', $zone->id)->where('is_active', true)->firstOrFail();

        $zoneIds = [$zone->id, ...$zone->descendantIds()];

        $listings = PropertyListing::query()
            ->with(['zone:id,name', 'owner:id,name,avatar', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])
            ->withViewerFavorite()
            ->where('status', 'active')
            ->whereIn('zone_id', $zoneIds)
            ->latest('published_at')
            ->paginate(9);

        $schema = $schemaMarkup->forArea($zone);

        return view('website.pages.area_landing', compact('zone', 'landingPage', 'listings', 'schema'));
    }

    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'zone_id' => ['required', 'integer', Rule::exists('zones', 'id')->where('is_active', true)],
        ]);

        $request->session()->put('zone_id', $request->integer('zone_id'));

        return back()->with('success', 'Location updated.');
    }

    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');

        $zone = Zone::nearest($lat, $lng, 'area') ?? Zone::nearest($lat, $lng, 'city');

        if ($zone) {
            $request->session()->put('zone_id', $zone->id);
        }

        return response()->json(['zone' => $zone ? ['id' => $zone->id, 'name' => $zone->name] : null]);
    }
}
