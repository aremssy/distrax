<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(): View
    {
        $languages = Language::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.languages.index', compact('languages'));
    }

    public function create(): View
    {
        return view('admin.languages.create');
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        // Demoting the old default and creating the new one must land together —
        // a failure in between would leave the app with no default language at all.
        DB::transaction(function () use ($request, $validated): void {
            if ($request->boolean('is_default')) {
                Language::where('is_default', true)->update(['is_default' => false]);
            }

            Language::create($validated);
        });

        return redirect()->route('admin.languages.index')->with('success', 'Language added.');
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.edit', compact('language'));
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($language, $request, $validated): void {
            if ($request->boolean('is_default')) {
                Language::where('is_default', true)->where('id', '!=', $language->id)->update(['is_default' => false]);
            }

            $language->update($validated);
        });

        return redirect()->route('admin.languages.index')->with('success', 'Language updated.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return back()->with('error', 'Cannot delete the default language.');
        }

        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', 'Language deleted.');
    }
}
