<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\PropertyListing;
use App\Services\OfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(private OfferService $offers)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $offers = Offer::query()
            ->with(['listing', 'negotiations'])
            ->where(fn ($q) => $q->where('buyer_id', $user->id)
                ->orWhereHas('listing', fn ($l) => $l->where('owner_id', $user->id)))
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('website.pages.offers.index', compact('offers'));
    }

    public function create(PropertyListing $property): View
    {
        abort_unless($property->status === 'active', 404);
        Gate::allowIf($property->owner_id !== auth()->id());

        return view('website.pages.offers.create', ['listing' => $property]);
    }

    public function store(Request $request, PropertyListing $property): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $offer = $this->offers->makeOffer($property, $request->user(), $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('offers.show', $offer)
            ->with('success', __('Your offer has been submitted. The seller has been notified.'));
    }

    public function show(Offer $offer): View
    {
        abort_unless($this->isParticipant($offer), 403);

        return view('website.pages.offers.show', [
            'offer' => $offer->load(['listing.owner', 'buyer', 'negotiations.sender', 'deal']),
            'isSeller' => auth()->id() === $offer->listing->owner_id,
        ]);
    }

    public function counter(Request $request, Offer $offer): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            if (auth()->id() === $offer->listing->owner_id) {
                $this->offers->sellerCounter($offer, $request->user(), $data);
            } else {
                $this->offers->buyerCounter($offer, $request->user(), $data);
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Counter offer submitted.'));
    }

    public function accept(Request $request, Offer $offer): RedirectResponse
    {
        try {
            $this->offers->accept($offer, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('offers.show', $offer)
            ->with('success', __('Offer accepted — a new deal is now open.'));
    }

    public function reject(Request $request, Offer $offer): RedirectResponse
    {
        try {
            $this->offers->reject($offer, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Offer rejected.'));
    }

    public function withdraw(Request $request, Offer $offer): RedirectResponse
    {
        try {
            $this->offers->withdraw($offer, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Offer withdrawn.'));
    }

    private function isParticipant(Offer $offer): bool
    {
        return auth()->id() === $offer->buyer_id
            || auth()->id() === $offer->listing->owner_id;
    }
}
