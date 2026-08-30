<?php

namespace App\Services;

use App\Models\HotelBooking;
use App\Models\PropertyListing;
use App\Models\Review;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VerifiedReviewGuard
{
    public function canReview(User $user, Model $reviewable): bool
    {
        if ($reviewable instanceof PropertyListing) {
            return HotelBooking::whereBelongsTo($user)
                ->whereBelongsTo($reviewable, 'listing')
                ->whereIn('status', ['checked_out', 'completed'])
                ->exists();
        }

        if ($reviewable instanceof Technician) {
            return TechnicianBooking::whereBelongsTo($user)
                ->whereBelongsTo($reviewable)
                ->where('status', 'completed')
                ->exists();
        }

        if ($reviewable instanceof User) {
            // Case A: reviewer is a tenant reviewing a listing owner
            $isTenantReviewingOwner = HotelBooking::whereBelongsTo($user)
                ->whereHas('listing', fn ($q) => $q->where('owner_id', $reviewable->id))
                ->whereIn('status', ['checked_out', 'completed'])
                ->exists();

            // Case B: reviewer is a listing owner reviewing a tenant who booked them
            $isOwnerReviewingTenant = HotelBooking::where('user_id', $reviewable->id)
                ->whereHas('listing', fn ($q) => $q->where('owner_id', $user->id))
                ->whereIn('status', ['checked_out', 'completed'])
                ->exists();

            // Case C: reviewer is a technician reviewing a customer who booked them
            $isTechnicianReviewingCustomer = Technician::where('user_id', $user->id)->exists()
                && TechnicianBooking::where('user_id', $reviewable->id)
                    ->whereHas('technician', fn ($q) => $q->where('user_id', $user->id))
                    ->where('status', 'completed')
                    ->exists();

            // Case D: reviewer is a buyer in a completed real-estate transaction against this seller
            $isBuyerReviewingSeller = Deal::where('seller_id', $reviewable->id)
                ->where('buyer_id', $user->id)
                ->where('stage', 'completed')
                ->exists();

            // Case E: reviewer is a seller in a completed real-estate transaction against this buyer
            $isSellerReviewingBuyer = Deal::where('seller_id', $user->id)
                ->where('buyer_id', $reviewable->id)
                ->where('stage', 'completed')
                ->exists();

            return $isTenantReviewingOwner || $isOwnerReviewingTenant || $isTechnicianReviewingCustomer
                || $isBuyerReviewingSeller || $isSellerReviewingBuyer;
        }

        return false;
    }

    public function markVerified(Review $review): bool
    {
        $canReview = $review->reviewable instanceof Model
            && $this->canReview($review->reviewer, $review->reviewable);

        $review->update(['is_verified' => $canReview]);

        return $canReview;
    }
}
