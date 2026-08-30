<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Negotiation;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles the offer lifecycle: create, withdraw, counter, accept / reject and
 * the resulting deal staging. Every transition is audited and notifies both
 * parties. Commissions are written when a deal is marked completed.
 */
class OfferService
{
    public const CLAIM_LEVELS = ['cash_investment', 'mortgage_financing', 'fix_flip', 'platform'];

    public function __construct(
        private NotificationDispatcher $dispatcher,
        private AuditLogger $audit,
        private SellerReputationService $reputation,
    ) {}

    /**
     * Create a new offer from a buyer against a listing.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function makeOffer(PropertyListing $listing, User $buyer, array $attributes): Offer
    {
        $this->assertCanOffer($listing, $buyer);

        $offer = DB::transaction(function () use ($listing, $buyer, $attributes) {
            $offer = Offer::create([
                'property_listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'amount' => (int) $attributes['amount'],
                'currency_code' => $attributes['currency_code'] ?? config('app.currency', 'USD'),
                'terms' => $attributes['terms'] ?? null,
                'expires_at' => $attributes['expires_at'] ?? now()->addDays(
                    (int) config('offer.default_expiry_days', 3),
                ),
            ]);

            if (! empty($attributes['message'])) {
                Negotiation::create([
                    'offer_id' => $offer->id,
                    'sender_id' => $buyer->id,
                    'amount' => $offer->amount,
                    'message' => $attributes['message'],
                    'status' => 'proposed',
                ]);
            }

            return $offer;
        });

        $this->notifySeller($listing, $offer, 'offer_status_change',
            __('You received a new offer'),
            __(':buyer has offered :amount on :title.', [
                'buyer' => $buyer->name,
                'amount' => money($offer->amount, $offer->currency_code),
                'title' => $listing->title,
            ]),
            $listing,
        );

        $this->audit->record("offer.created", $offer, ['amount' => $offer->amount]);

        return $offer;
    }

    /**
     * Buyer withdraws a pending / countered offer.
     */
    public function withdraw(Offer $offer, User $actor): void
    {
        $this->assertParticipant($offer, $actor, 'buyer');

        if (! in_array($offer->status, ['pending', 'countered'], true)) {
            throw new RuntimeException(__('This offer can no longer be withdrawn.'));
        }

        $offer->update(['status' => 'withdrawn']);

        $this->notifySeller($offer->listing, $offer, 'offer_status_change',
            __('Offer withdrawn'),
            __('An offer on :title was withdrawn.', ['title' => $offer->listing->title]),
            $offer->listing,
        );

        $this->audit->record('offer.withdrawn', $offer);
    }

    /**
     * The seller counters the buyer's offer. The buyer then responds.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sellerCounter(Offer $offer, User $seller, array $attributes): Negotiation
    {
        $this->assertParticipant($offer, $seller, 'seller');

        if ($offer->status !== 'pending') {
            throw new RuntimeException(__('Only a pending offer can be countered.'));
        }

        $this->recordSellerResponse($offer);

        $negotiation = DB::transaction(function () use ($offer, $seller, $attributes) {
            $negotiation = Negotiation::create([
                'offer_id' => $offer->id,
                'sender_id' => $seller->id,
                'amount' => (int) $attributes['amount'],
                'message' => $attributes['message'] ?? null,
                'status' => 'proposed',
            ]);

            $offer->update([
                'status' => 'countered',
                'amount' => $negotiation->amount,
            ]);

            return $negotiation;
        });

        $this->notifyBuyer($offer, 'offer_status_change',
            __('You received a counter offer'),
            __(':seller countered :amount on :title.', [
                'seller' => $seller->name,
                'amount' => money($negotiation->amount, $offer->currency_code),
                'title' => $offer->listing->title,
            ]),
            $offer->listing,
        );

        $this->audit->record('offer.countered', $offer, ['amount' => $negotiation->amount]);

        return $negotiation;
    }

    /**
     * The buyer counters the seller's counter offer.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function buyerCounter(Offer $offer, User $buyer, array $attributes): Negotiation
    {
        $this->assertParticipant($offer, $buyer, 'buyer');

        if ($offer->status !== 'countered') {
            throw new RuntimeException(__('There is no open counter to respond to.'));
        }

        $negotiation = DB::transaction(function () use ($offer, $buyer, $attributes) {
            $negotiation = Negotiation::create([
                'offer_id' => $offer->id,
                'sender_id' => $buyer->id,
                'amount' => (int) $attributes['amount'],
                'message' => $attributes['message'] ?? null,
                'status' => 'proposed',
            ]);

            $offer->update(['status' => 'pending']);

            return $negotiation;
        });

        $this->notifySeller($offer->listing, $offer, 'offer_status_change',
            __('New counter offer'),
            __(':buyer countered :amount on :title.', [
                'buyer' => $buyer->name,
                'amount' => money($negotiation->amount, $offer->currency_code),
                'title' => $offer->listing->title,
            ]),
            $offer->listing,
        );

        $this->audit->record('offer.countered', $offer, ['amount' => $negotiation->amount]);

        return $negotiation;
    }

    /**
     * Either party accepts the currently agreed amount, creating a Deal.
     */
    public function accept(Offer $offer, User $actor): Deal
    {
        $this->assertParticipant($offer, $actor);

        if (! in_array($offer->status, ['pending', 'countered'], true)) {
            throw new RuntimeException(__('This offer is no longer active.'));
        }

        if ($actor->id === $offer->listing->owner_id) {
            $this->recordSellerResponse($offer);
        }

        $deal = DB::transaction(function () use ($offer) {
            $offer->update(['status' => 'accepted']);

            return Deal::create([
                'property_listing_id' => $offer->property_listing_id,
                'offer_id' => $offer->id,
                'buyer_id' => $offer->buyer_id,
                'seller_id' => $offer->listing->owner_id,
                'agreed_amount' => $offer->amount,
                'currency_code' => $offer->currency_code,
                'stage' => 'offer_accepted',
            ]);
        });

        $other = $actor->id === $offer->buyer_id ? $offer->listing->owner : $offer->buyer;
        $this->dispatcher->send(
            $other,
            'offer_status_change',
            __('Offer accepted'),
            __('The offer on :title has been accepted — a deal is now open.', ['title' => $offer->listing->title]),
            ['deal_id' => $deal->id, 'offer_id' => $offer->id],
            $deal,
        );

        $this->audit->record('deal.created', $deal, ['stage' => 'offer_accepted']);

        return $deal;
    }

    /**
     * Reject an offer, closing it and notifying the counterpart.
     */
    public function reject(Offer $offer, User $actor): void
    {
        $this->assertParticipant($offer, $actor);

        if ($offer->status === 'accepted') {
            throw new RuntimeException(__('This offer has already been accepted.'));
        }

        if ($actor->id === $offer->listing->owner_id) {
            $this->recordSellerResponse($offer);
        }

        $offer->update(['status' => 'rejected']);

        $other = $actor->id === $offer->buyer_id ? $offer->listing->owner : $offer->buyer;
        $this->dispatcher->send(
            $other,
            'offer_status_change',
            __('Offer :status', ['status' => __('rejected')]),
            __('The offer on :title was rejected.', ['title' => $offer->listing->title]),
            [],
            $offer,
        );

        $this->audit->record('offer.rejected', $offer);
    }

    /**
     * Advance a deal to the next stage, guarded by the legal-matter check at the
     * closing boundary. Writes the transaction commission when completed.
     */
    public function advanceStage(Deal $deal, string $stage): Deal
    {
        $order = ['offer_accepted', 'inspection', 'legal_review', 'closing', 'completed'];
        $current = $deal->stage;
        $currentIndex = array_search($current, $order, true);
        $targetIndex = array_search($stage, $order, true);

        if ($currentIndex === false || $targetIndex === false || $targetIndex !== $currentIndex + 1) {
            throw new RuntimeException(__('Invalid stage transition.'));
        }

        if ($stage === 'closing' && $deal->legalMatters()->where('status', 'issue_found')->exists()) {
            throw new RuntimeException(__('A legal matter must be resolved before closing.'));
        }

        if ($stage === 'completed') {
            DB::transaction(function () use ($deal) {
                $deal->update(['stage' => 'completed', 'closed_at' => now()]);
                $this->writeCommission($deal);
                $deal->listing->update(['status' => 'sold']);
                $this->reputation->recordCompletedDeal($deal);
            });
        } else {
            $deal->update(['stage' => $stage]);
        }

        $this->audit->record('deal.stage_changed', $deal, ['from' => $current, 'to' => $stage]);

        return $deal;
    }

    private function writeCommission(Deal $deal): void
    {
        $rate = (float) setting('commission_rate', 0.03);
        $amount = (int) round($deal->agreed_amount * $rate);
        $currency = $deal->currency_code ?: setting('default_currency', 'BDT');

        Payment::create([
            'user_id' => $deal->seller_id,
            'payable_type' => $deal->getMorphClass(),
            'payable_id' => $deal->id,
            'property_listing_id' => $deal->property_listing_id,
            'gateway' => null,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'purpose' => 'transaction_commission',
            'discount_amount' => 0,
        ]);
    }

    private function recordSellerResponse(Offer $offer): void
    {
        $seller = $offer->listing->owner;
        $minutes = max(0, (int) $offer->created_at->diffInMinutes(now()));
        $this->reputation->recordOfferResponse($seller, $minutes);
    }

    private function assertCanOffer(PropertyListing $listing, User $buyer): void
    {
        if ($listing->user_id === $buyer->id) {
            throw new RuntimeException(__('You cannot make an offer on your own listing.'));
        }

        if (! in_array($listing->status, ['active'], true)) {
            throw new RuntimeException(__('This property is no longer accepting offers.'));
        }

        if (Offer::where('property_listing_id', $listing->id)
            ->where('buyer_id', $buyer->id)
            ->whereIn('status', ['pending', 'countered', 'accepted'])
            ->exists()) {
            throw new RuntimeException(__('You already have an active offer on this property.'));
        }
    }

    private function assertParticipant(Offer $offer, User $actor, ?string $role = null): void
    {
        $isBuyer = $actor->id === $offer->buyer_id;
        $isSeller = $actor->id === $offer->listing->owner_id;

        if (! $isBuyer && ! $isSeller) {
            throw new RuntimeException(__('You are not a participant in this offer.'));
        }

        if ($role === 'buyer' && ! $isBuyer) {
            throw new RuntimeException(__('Only the buyer can perform this action.'));
        }

        if ($role === 'seller' && ! $isSeller) {
            throw new RuntimeException(__('Only the seller can perform this action.'));
        }
    }

    private function notifyBuyer(Offer $offer, string $type, string $title, string $body, ?PropertyListing $listing = null): void
    {
        $this->dispatcher->send($offer->buyer, $type, $title, $body, [], $listing);
    }

    private function notifySeller(PropertyListing $listing, Offer $offer, string $type, string $title, string $body, ?PropertyListing $notifiable = null): void
    {
        $this->dispatcher->send($listing->owner, $type, $title, $body, [], $notifiable);
    }
}
