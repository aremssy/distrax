<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeoMetaRequest;
use App\Http\Requests\Admin\UpdateSeoMetaRequest;
use App\Models\SeoMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SeoMetaController extends Controller
{
    public function index(Request $request): View
    {
        // The index only renders seoable_type/seoable_id, so the polymorphic relation
        // is intentionally not eager-loaded.
        $meta = SeoMeta::query()
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('seoable_type', $type))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.seo.index', compact('meta'));
    }

    /**
     * Return the selectable records for a seoable type, so the create form can
     * offer a labelled dropdown instead of a blind numeric ID field.
     */
    public function records(Request $request): JsonResponse
    {
        $type = $request->string('type')->value();

        if (! array_key_exists($type, SeoMeta::SEOABLE_TYPES)) {
            return response()->json(['records' => []]);
        }

        $labelColumn = SeoMeta::SEOABLE_TYPES[$type];

        $records = $type::query()
            ->latest()
            ->limit(500)
            ->get(['id', $labelColumn])
            ->map(fn ($record): array => [
                'id' => $record->id,
                'label' => $record->{$labelColumn} ?: '#'.$record->id,
            ]);

        return response()->json(['records' => $records]);
    }

    public function store(StoreSeoMetaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Only touch og_image when a file was actually uploaded — otherwise a
        // re-submitted create form would null out an existing record's image.
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        } else {
            unset($data['og_image']);
        }

        $existing = SeoMeta::where('seoable_type', $data['seoable_type'])
            ->where('seoable_id', $data['seoable_id'])
            ->first();

        $oldImage = $existing?->og_image;

        SeoMeta::updateOrCreate(
            ['seoable_type' => $data['seoable_type'], 'seoable_id' => $data['seoable_id']],
            $data
        );

        if (isset($data['og_image']) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.seo.index')->with('success', 'SEO meta saved.');
    }

    public function show(SeoMeta $seoMeta): View
    {
        return view('admin.seo.show', [
            'meta' => $seoMeta->load('seoable'),
        ]);
    }

    public function update(UpdateSeoMetaRequest $request, SeoMeta $seoMeta): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('og_image')) {
            $oldImage = $seoMeta->og_image;
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        } else {
            unset($data['og_image']);
        }

        $seoMeta->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.seo.show', $seoMeta)->with('success', 'SEO meta updated.');
    }

    public function destroy(SeoMeta $seoMeta): RedirectResponse
    {
        if ($seoMeta->og_image && ! str_starts_with($seoMeta->og_image, 'http')) {
            Storage::disk('public')->delete($seoMeta->og_image);
        }

        $seoMeta->delete();

        return redirect()->route('admin.seo.index')->with('success', 'SEO meta deleted.');
    }
}
