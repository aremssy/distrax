<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCurrencyRequest;
use App\Http\Requests\Admin\UpdateCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(): View
    {
        $currencies = Currency::orderBy('sort_order')->orderBy('code')->get();

        return view('admin.currencies.index', compact('currencies'));
    }

    public function create(): View
    {
        return view('admin.currencies.create');
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Demoting the old default and creating the new one must land together —
        // a failure in between would leave the app with no default currency at all.
        DB::transaction(function () use ($request, $validated): void {
            if ($request->boolean('is_default')) {
                Currency::where('is_default', true)->update(['is_default' => false]);
            }

            Currency::create($validated);
        });

        Cache::forget('currencies.all');

        return redirect()->route('admin.currencies.index')->with('success', 'Currency added.');
    }

    public function edit(Currency $currency): View
    {
        return view('admin.currencies.edit', compact('currency'));
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($currency, $request, $validated): void {
            if ($request->boolean('is_default')) {
                Currency::where('is_default', true)->where('id', '!=', $currency->id)->update(['is_default' => false]);
            }

            $currency->update($validated);
        });

        Cache::forget('currencies.all');
        Cache::forget("currency.{$currency->code}");

        return redirect()->route('admin.currencies.index')->with('success', 'Currency updated.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->with('error', 'Cannot delete the default currency.');
        }

        Cache::forget("currency.{$currency->code}");
        $currency->delete();

        return redirect()->route('admin.currencies.index')->with('success', 'Currency deleted.');
    }
}
