<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Tenancy;
use App\Services\AgreementGenerator;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    use GatesRentManagementFeatures;

    public function index(Request $request, PostEntitlementService $entitlement): View
    {
        $user = $request->user();
        $hasAccess = $this->hasFeature($entitlement->unlockedFeatures($user), 'agreement_templates');

        $templates = $hasAccess
            ? AgreementTemplate::where('owner_id', $user->id)->orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        $agreements = $hasAccess
            ? Agreement::whereHas('tenancy', fn ($q) => $q->where('owner_id', $user->id))->with('tenancy.listing')->orderByDesc('created_at')->get()
            : collect();

        $tenancies = Tenancy::where('owner_id', $user->id)->where('status', 'active')->with(['listing:id,title', 'tenant:id,name'])->get();

        return view('website.owner.rent-management.agreements.index', compact('hasAccess', 'templates', 'agreements', 'tenancies'));
    }

    public function storeTemplate(Request $request, PostEntitlementService $entitlement): RedirectResponse
    {
        $user = $request->user();

        if (! $this->hasFeature($entitlement->unlockedFeatures($user), 'agreement_templates')) {
            return back()->withErrors(['limit' => 'Agreement templates require an active subscription with that feature.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        $validated['is_default'] = $request->boolean('is_default');

        AgreementTemplate::create(['owner_id' => $user->id, ...$validated]);

        return back()->with('success', 'Template saved.');
    }

    public function destroyTemplate(AgreementTemplate $agreementTemplate, Request $request): RedirectResponse
    {
        abort_unless($agreementTemplate->owner_id === $request->user()->id, 404);
        $agreementTemplate->delete();

        return back()->with('success', 'Template deleted.');
    }

    public function generate(Request $request, PostEntitlementService $entitlement, AgreementGenerator $generator): RedirectResponse
    {
        $user = $request->user();

        if (! $this->hasFeature($entitlement->unlockedFeatures($user), 'agreement_templates')) {
            return back()->withErrors(['limit' => 'Generating agreements requires the agreement_templates feature.']);
        }

        $validated = $request->validate([
            'tenancy_id' => ['required', 'integer', 'exists:tenancies,id'],
            'agreement_template_id' => ['nullable', 'integer', 'exists:agreement_templates,id'],
        ]);

        $tenancy = Tenancy::with(['listing', 'owner'])->findOrFail($validated['tenancy_id']);
        $this->authorize('view', $tenancy);

        $template = ! empty($validated['agreement_template_id'])
            ? AgreementTemplate::where('owner_id', $user->id)->findOrFail($validated['agreement_template_id'])
            : AgreementTemplate::where('owner_id', $user->id)->where('is_default', true)->first();

        $generator->generate($tenancy, $template);

        return back()->with('success', 'Agreement generated.');
    }

    public function show(Agreement $agreement): View
    {
        $this->authorize('view', $agreement->tenancy);
        $agreement->load('tenancy.listing');

        return view('website.owner.rent-management.agreements.show', compact('agreement'));
    }
}
