<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePlanFeatureRequest;
use App\Models\PlanFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PlanFeatureController extends Controller
{
    public function store(SavePlanFeatureRequest $request): RedirectResponse
    {
        $label = $request->validated('label');
        $key = Str::snake($label);

        if (PlanFeature::where('key', $key)->exists()) {
            return back()->withErrors(['label' => 'A feature with this name already exists.']);
        }

        PlanFeature::create([
            'key' => $key,
            'label' => $label,
            'sort_order' => PlanFeature::max('sort_order') + 1,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Feature added.');
    }

    public function destroy(PlanFeature $planFeature): RedirectResponse
    {
        $planFeature->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Feature removed.');
    }
}
