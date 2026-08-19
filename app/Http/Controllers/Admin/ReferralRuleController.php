<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveReferralRuleRequest;
use App\Models\ReferralRule;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferralRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.referral-rules.index', [
            'rules' => ReferralRule::orderBy('reward_value')->paginate(25),
        ]);
    }

    public function store(SaveReferralRuleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = UniqueSlug::make(ReferralRule::class, $data['name'], 'rule');
        $data['is_active'] = $request->boolean('is_active', true);

        ReferralRule::create($data);

        return redirect()->route('admin.referral-rules.index')->with('success', 'Rule created.');
    }

    public function show(ReferralRule $referralRule): View
    {
        return view('admin.referral-rules.show', ['rule' => $referralRule]);
    }

    public function update(SaveReferralRuleRequest $request, ReferralRule $referralRule): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = UniqueSlug::make(ReferralRule::class, $data['name'], 'rule', $referralRule->id);
        $data['is_active'] = $request->boolean('is_active', $referralRule->is_active);

        $referralRule->update($data);

        return redirect()->route('admin.referral-rules.show', $referralRule)->with('success', 'Rule updated.');
    }

    public function destroy(ReferralRule $referralRule): RedirectResponse
    {
        $referralRule->delete();

        return redirect()->route('admin.referral-rules.index')->with('success', 'Rule deleted.');
    }
}
