<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\LedgerTransaction;
use App\Models\PropertyListing;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    use GatesRentManagementFeatures;

    public function index(Request $request): View
    {
        $user = $request->user();

        $transactions = LedgerTransaction::where('owner_id', $user->id)->orderByDesc('occurred_on')->paginate(15);
        $income = (int) LedgerTransaction::where('owner_id', $user->id)->where('type', 'income')->sum('amount');
        $expense = (int) LedgerTransaction::where('owner_id', $user->id)->where('type', 'expense')->sum('amount');
        $listings = PropertyListing::where('owner_id', $user->id)->orderBy('title')->get(['id', 'title']);

        return view('website.owner.rent-management.ledger.index', compact('transactions', 'income', 'expense', 'listings'));
    }

    public function store(Request $request, PostEntitlementService $entitlement): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'property_listing_id' => ['nullable', 'integer', 'exists:property_listings,id'],
        ]);

        $requiredFeature = $validated['type'] === 'income' ? 'income_tracking' : 'expense_tracking';

        if (! $this->hasFeature($entitlement->unlockedFeatures($user), $requiredFeature)) {
            return back()->withErrors(['limit' => "Recording {$validated['type']} requires the {$requiredFeature} feature."])->withInput();
        }

        if (! empty($validated['property_listing_id'])) {
            PropertyListing::where('id', $validated['property_listing_id'])->where('owner_id', $user->id)->firstOrFail();
        }

        LedgerTransaction::create(['owner_id' => $user->id, ...$validated]);

        return back()->with('success', 'Transaction recorded.');
    }
}
