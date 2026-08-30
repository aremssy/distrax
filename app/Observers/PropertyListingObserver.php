<?php

namespace App\Observers;

use App\Jobs\MatchSavedSearchesJob;
use App\Models\PropertyListing;
use App\Services\PropertyTimelineService;
use App\Services\VerificationCaseService;

class PropertyListingObserver
{
    /**
     * Fire the saved-search matching job when a listing becomes active
     * (covers both initial admin approval and manual re-activation).
     */
    public function updated(PropertyListing $listing): void
    {
        $timeline = app(PropertyTimelineService::class);

        if ($listing->wasChanged('status')) {
            $timeline->recordStatusChange($listing, $listing->getOriginal('status'), $listing->status);

            if ($listing->status === 'active') {
                $timeline->recordListed($listing);
                MatchSavedSearchesJob::dispatch($listing->id);

                // "Active" is the verification-eligible state: the same transition ListingController::approve() uses.
                app(VerificationCaseService::class)->openCase($listing);
            }
        }

        if ($listing->wasChanged('price')) {
            $timeline->recordPriceChange(
                $listing,
                $listing->getOriginal('price'),
                $listing->price,
                auth()->id()
            );

            app(\App\Services\IntelligenceService::class)->recomputeScore($listing);
        }
    }
}
