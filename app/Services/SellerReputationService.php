<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Disclosure;
use App\Models\Offer;
use App\Models\PropertyListing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Computes and maintains seller (and counterparty) reputation signals for the
 * real-estate marketplace. Reputation is surfaced as transparent underlying
 * statistics, never as an implied guarantee of trustworthiness.
 */
class SellerReputationService
{
    private const RATING_CACHE_TTL = 3600;

    /**
     * Recompute the weighted average rating for a user from verified, visible
     * reviews left against them or against their completed deals.
     */
    public function recomputeRating(User $user): float
    {
        $count = 0;
        $sum = 0.0;

        $direct = Review::where('is_visible', true)
            ->where('is_verified', true)
            ->where('reviewable_type', $user->getMorphClass())
            ->where('reviewable_id', $user->id)
            ->whereNotNull('rating')
            ->get(['rating']);

        foreach ($direct as $review) {
            $sum += (float) $review->rating;
            $count++;
        }

        $dealIds = $user->dealsAsSeller()
            ->where('stage', 'completed')
            ->pluck('id');

        $dealReviews = Review::where('is_visible', true)
            ->where('is_verified', true)
            ->whereNotNull('rating')
            ->whereIn('deal_id', $dealIds)
            ->get(['rating']);

        foreach ($dealReviews as $review) {
            $sum += (float) $review->rating;
            $count++;
        }

        $rating = $count > 0 ? round($sum / $count, 2) : 0.0;

        $user->forceFill(['rating' => $rating])->save();
        Cache::forget("seller_reputation.{$user->id}");

        return $rating;
    }

    /**
     * Increment completed-transaction counts for both parties once a deal closes.
     */
    public function recordCompletedDeal(Deal $deal): void
    {
        DB::transaction(function () use ($deal) {
            $deal->seller()?->increment('completed_deals_count');
            $deal->buyer()?->increment('completed_deals_count');
            $this->recomputeRating($deal->seller);
        });
    }

    /**
     * Fold a seller's response latency (minutes from offer to first action) into
     * their exponentially-weighted running average.
     */
    public function recordOfferResponse(User $seller, float $responseMinutes): void
    {
        $current = (float) ($seller->response_time_avg_minutes ?? 0);

        $next = $current <= 0
            ? $responseMinutes
            : (0.85 * $current) + (0.15 * $responseMinutes);

        $seller->forceFill(['response_time_avg_minutes' => (int) round($next)])->save();
    }

    /**
     * Proportion of received offers the seller has acted on (accepted, countered,
     * or rejected) rather than letting expire or be withdrawn.
     */
    public function offerResponseRate(User $seller): ?float
    {
        $total = Offer::whereHas('listing', fn ($q) => $q->where('owner_id', $seller->id))->count();

        if ($total === 0) {
            return null;
        }

        $responded = Offer::whereHas('listing', fn ($q) => $q->where('owner_id', $seller->id))
            ->whereIn('status', ['accepted', 'countered', 'rejected'])
            ->count();

        return round($responded / $total, 2);
    }

    /**
     * Compile the full set of transparent reputation signals for a seller.
     *
     * @return array<string, mixed>
     */
    public function statsFor(User $seller): array
    {
        return Cache::remember("seller_reputation.{$seller->id}", self::RATING_CACHE_TTL, function () use ($seller) {
            $totalListings = PropertyListing::where('owner_id', $seller->id)->count();
            $disclosureCount = Disclosure::whereHas('listing', fn ($q) => $q->where('owner_id', $seller->id))->count();

            return [
                'identity_verified' => $seller->verification_status === 'verified',
                'rating' => (float) $seller->rating,
                'response_time_avg_minutes' => $seller->response_time_avg_minutes,
                'offer_response_rate' => $this->offerResponseRate($seller),
                'completed_deals_count' => (int) $seller->completed_deals_count,
                'total_listings' => $totalListings,
                'disclosure_count' => $disclosureCount,
                'seller_type' => $seller->seller_type,
                'company_name' => $seller->company_name,
            ];
        });
    }
}
