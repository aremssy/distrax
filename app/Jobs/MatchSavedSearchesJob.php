<?php

namespace App\Jobs;

use App\Models\PropertyListing;
use App\Models\SavedSearch;
use App\Notifications\SavedSearchAlertNotification;
use App\Services\SavedSearchMatcherService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatched from PropertyListingObserver, which fires inside the listing
 * create/update transaction. Without ShouldQueueAfterCommit a worker can pick the
 * job up before that transaction commits and find no listing (or a stale status),
 * silently dropping every saved-search alert for that listing.
 */
class MatchSavedSearchesJob implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly int $listingId) {}

    public function handle(SavedSearchMatcherService $matcher): void
    {
        // Respect the global admin toggle
        if (! setting('saved_search_alert_enabled')) {
            return;
        }

        $listing = PropertyListing::find($this->listingId);

        if (! $listing || $listing->status !== 'active') {
            return;
        }

        SavedSearch::query()
            ->where('alert_on', true)
            ->with('user')
            ->chunkById(100, function ($searches) use ($listing, $matcher): void {
                foreach ($searches as $search) {
                    $user = $search->user;

                    if (! $user || $user->is_blocked || $user->trashed()) {
                        continue;
                    }

                    // Skip if the search owner is the listing owner (no need to alert yourself)
                    if ($user->id === $listing->owner_id) {
                        continue;
                    }

                    if (! $matcher->matches($listing, $search->criteria)) {
                        continue;
                    }

                    $user->notify(new SavedSearchAlertNotification($listing, $search));

                    $search->forceFill(['last_alerted_at' => now()])->saveQuietly();
                }
            });
    }
}
