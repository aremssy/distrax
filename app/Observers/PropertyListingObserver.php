<?php

namespace App\Observers;

use App\Jobs\MatchSavedSearchesJob;
use App\Models\PropertyListing;

class PropertyListingObserver
{
    /**
     * Fire the saved-search matching job when a listing becomes active
     * (covers both initial admin approval and manual re-activation).
     */
    public function updated(PropertyListing $listing): void
    {
        if ($listing->wasChanged('status') && $listing->status === 'active') {
            MatchSavedSearchesJob::dispatch($listing->id);
        }
    }
}
