<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\OfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DealController extends Controller
{
    public function index(Request $request): View
    {
        $deals = Deal::with(['listing:id,title,slug,status', 'buyer:id,name', 'seller:id,name'])
            ->when($request->string('stage')->value(), fn ($q, $s) => $q->where('stage', $s))
            ->when($request->string('q')->value(), function ($q, $term) {
                $q->whereHas('listing', fn ($l) => $l->where('title', 'like', "%{$term}%"))
                    ->orWhereHas('buyer', fn ($b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('seller', fn ($s) => $s->where('name', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.deals.index', compact('deals'));
    }

    public function show(Deal $deal): View
    {
        $deal->load(['listing', 'offer', 'buyer', 'seller', 'legalMatters.assignee', 'documents']);

        return view('admin.deals.show', compact('deal'));
    }

    public function advance(Request $request, Deal $deal, OfferService $offers): RedirectResponse
    {
        $validated = $request->validate(['stage' => ['required', 'string', 'max:50']]);

        try {
            $offers->advanceStage($deal, $validated['stage']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deal advanced to '.$validated['stage'].'.');
    }

    public function cancel(Request $request, Deal $deal): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $deal->update(['stage' => 'fell_through']);

        return back()->with('success', 'Deal marked as fell through.');
    }
}
