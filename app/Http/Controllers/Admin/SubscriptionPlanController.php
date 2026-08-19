<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSubscriptionPlanRequest;
use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use App\Services\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    use ReordersRecords;

    public function index()
    {
        return view('admin.plans.index', [
            'plans' => SubscriptionPlan::withCount('userSubscriptions')->orderBy('sort_order')->orderBy('price')->paginate(25),
            'available_features' => PlanFeature::orderBy('sort_order')->get(),
        ]);
    }

    public function store(SaveSubscriptionPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = UniqueSlug::make(SubscriptionPlan::class, $data['name'], 'plan');
        $data['sort_order'] = $this->nextSortOrder(SubscriptionPlan::class);

        SubscriptionPlan::create($data);

        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function show(SubscriptionPlan $plan): RedirectResponse
    {
        return redirect()->route('admin.plans.index');
    }

    public function update(SaveSubscriptionPlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = UniqueSlug::make(SubscriptionPlan::class, $data['name'], 'plan', $plan->id);

        $plan->update($data);

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->userSubscriptions()->exists()) {
            return redirect()->route('admin.plans.index')->with('error', 'Plans with subscriptions cannot be deleted. Deactivate instead.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted.');
    }

    public function toggle(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return redirect()->route('admin.plans.index')->with('success', 'Plan status toggled.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:subscription_plans,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(SubscriptionPlan::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
