<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTranslationRequest;
use App\Http\Requests\Admin\UpdateTranslationRequest;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TranslationController extends Controller
{
    public function index(Request $request): View
    {
        $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
        $localeId = $request->integer('language_id', $languages->firstWhere('is_default', true)?->id ?? $languages->first()?->id);
        $search = $request->string('search');

        $query = Translation::where('language_id', $localeId);

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $translations = $query->orderBy('group')->orderBy('key')->paginate(50)->withQueryString();

        return view('admin.translations.index', compact('languages', 'localeId', 'translations', 'search'));
    }

    public function store(StoreTranslationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Translation::updateOrCreate(
            ['language_id' => $validated['language_id'], 'group' => $validated['group'], 'key' => $validated['key']],
            ['value' => $validated['value']]
        );

        $this->flushGroupCache($validated['language_id'], $validated['group']);

        return back()->with('success', 'Translation saved.');
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): RedirectResponse
    {
        $validated = $request->validated();

        $translation->update($validated);
        $this->flushGroupCache($translation->language_id, $translation->group);

        return back()->with('success', 'Updated.');
    }

    public function destroy(Translation $translation): RedirectResponse
    {
        $this->flushGroupCache($translation->language_id, $translation->group);
        $translation->delete();

        return back()->with('success', 'Deleted.');
    }

    private function flushGroupCache(int $languageId, string $group): void
    {
        $locale = Language::find($languageId)?->code;
        if ($locale) {
            Cache::forget("translations.{$locale}.{$group}");
        }
    }
}
