<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCmsPageRequest;
use App\Http\Requests\Admin\UpdateCmsPageRequest;
use App\Models\CmsPage;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function index(Request $request): View
    {
        $pages = CmsPage::with('creator:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.cms.index', compact('pages'));
    }

    public function store(StoreCmsPageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['featured_image'] = $request->file('featured_image')?->store('cms', 'public');
        $data['slug'] ??= UniqueSlug::make(CmsPage::class, $data['title'], 'page');
        $data['created_by'] = auth()->id();
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        CmsPage::create($data);

        return redirect()->route('admin.cms.index')->with('success', 'Page created.');
    }

    public function show(CmsPage $cmsPage): View
    {
        return view('admin.cms.show', [
            'page' => $cmsPage->load(['creator:id,name,email', 'seoMeta']),
        ]);
    }

    public function update(UpdateCmsPageRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $oldImage = $cmsPage->featured_image;
            $data['featured_image'] = $request->file('featured_image')->store('cms', 'public');
        } else {
            unset($data['featured_image']);
        }

        $data['slug'] ??= UniqueSlug::make(CmsPage::class, $data['title'], 'page', $cmsPage->id);
        $data['published_at'] = $data['status'] === 'published' && ! $cmsPage->published_at ? now() : $cmsPage->published_at;

        $cmsPage->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.cms.show', $cmsPage)->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $cmsPage): RedirectResponse
    {
        $cmsPage->delete();

        return redirect()->route('admin.cms.index')->with('success', 'Page deleted.');
    }
}
