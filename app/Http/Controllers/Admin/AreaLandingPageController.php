<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAreaLandingPageRequest;
use App\Http\Requests\Admin\UpdateAreaLandingPageRequest;
use App\Models\AreaLandingPage;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AreaLandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $pages = AreaLandingPage::with('zone:id,name,type')
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->latest()
            ->paginate(25);

        return view('admin.area-landing-pages.index', compact('pages'));
    }

    public function store(StoreAreaLandingPageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['featured_image'] = $request->file('featured_image')?->store('area-landing-pages', 'public');
        $data['features'] = array_values(array_unique($data['features'] ?? []));
        $data['is_active'] = $request->boolean('is_active', true);

        AreaLandingPage::create($data);

        return redirect()->route('admin.area-landing-pages.index')->with('success', 'Landing page created.');
    }

    public function show(AreaLandingPage $areaLandingPage): View
    {
        return view('admin.area-landing-pages.show', [
            'page' => $areaLandingPage->load('zone:id,name,type'),
        ]);
    }

    public function update(UpdateAreaLandingPageRequest $request, AreaLandingPage $areaLandingPage): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $oldImage = $areaLandingPage->featured_image;
            $data['featured_image'] = $request->file('featured_image')->store('area-landing-pages', 'public');
        } else {
            unset($data['featured_image']);
        }

        $data['features'] = array_values(array_unique($data['features'] ?? []));
        $data['is_active'] = $request->boolean('is_active', $areaLandingPage->is_active);

        $areaLandingPage->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.area-landing-pages.show', $areaLandingPage)->with('success', 'Landing page updated.');
    }

    public function destroy(AreaLandingPage $areaLandingPage): RedirectResponse
    {
        $areaLandingPage->delete();

        return redirect()->route('admin.area-landing-pages.index')->with('success', 'Landing page deleted.');
    }

    public function zones(): JsonResponse
    {
        $zones = Zone::whereDoesntHave('landingPage')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json(['zones' => $zones]);
    }
}
