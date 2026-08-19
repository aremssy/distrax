<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBannerRequest;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    use ReordersRecords;

    public function index(Request $request): View
    {
        $banners = Banner::query()
            ->when($request->string('position')->value(), fn ($query, string $position) => $query->where('position', $position))
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(25);

        return view('admin.banners.index', compact('banners'));
    }

    public function store(SaveBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['image'] = $request->file('image')?->store('banners', 'public');
        $data['sort_order'] = $this->nextSortOrder(Banner::class);
        $data['is_active'] = $request->boolean('is_active', true);

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function show(Banner $banner): View
    {
        return view('admin.banners.show', compact('banner'));
    }

    public function update(SaveBannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();

        $data['sort_order'] = $data['sort_order'] ?? $banner->sort_order;
        $data['is_active'] = $request->boolean('is_active', $banner->is_active);

        if ($request->hasFile('image')) {
            $oldImage = $banner->image;
            $data['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($data['image']);
        }

        $banner->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.banners.show', $banner)->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:banners,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(Banner::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
