<?php

namespace App\Console\Commands;

use App\Models\PropertyListing;
use App\Models\SavedSearch;
use App\Services\NotificationDispatcher;
use App\Services\SavedSearchMatcherService;
use Carbon\CarbonInterval;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('radar:digests {frequency=daily : daily|weekly}')]
#[Description('Send deal-radar digest notifications to mandate searches on their daily/weekly schedule.')]
class SendRadarDigests extends Command
{
    public function handle(NotificationDispatcher $dispatcher, SavedSearchMatcherService $matcher): int
    {
        $frequency = $this->argument('frequency');
        abort_unless(in_array($frequency, ['daily', 'weekly'], true), 1);

        $window = $frequency === 'daily' ? CarbonInterval::day() : CarbonInterval::week();
        $since = now()->sub($window);

        $targets = SavedSearch::query()
            ->where('is_mandate', true)
            ->where('frequency', $frequency)
            ->where('alert_on', true)
            ->with('user')
            ->get();

        $matched = 0;

        foreach ($targets as $search) {
            $user = $search->user;
            if (! $user || $user->is_blocked || $user->trashed()) {
                continue;
            }

            $listings = PropertyListing::query()
                ->where('status', 'active')
                ->where('created_at', '>', $since)
                ->limit(500)
                ->get()
                ->filter(fn (PropertyListing $listing) => $listing->owner_id !== $user->id)
                ->filter(fn (PropertyListing $listing) => $matcher->matches($listing, $this->criteriaFor($search)));

            if ($listings->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($search, $listings, $dispatcher, $user) {
                $dispatcher->send(
                    $user,
                    'new_matched_deal',
                    __(':count new :type match', [
                        'count' => $listings->count(),
                        'type' => $search->name ?: __('deal radar'),
                    ]),
                    __('New properties matching your deal radar criteria were found.'),
                    ['search_id' => $search->id, 'count' => $listings->count()],
                );

                $search->forceFill(['last_alerted_at' => now()])->saveQuietly();
            });

            $matched++;
        }

        $this->info("Sent {$frequency} digests for {$matched} mandate(s).");

        return self::SUCCESS;
    }

    /**
     * Merge the deal-radar floors (stored on the row) into the search criteria
     * so the shared matcher can evaluate them.
     *
     * @return array<string, mixed>
     */
    private function criteriaFor(SavedSearch $search): array
    {
        $criteria = (array) $search->criteria;

        if (is_numeric($search->min_deal_score)) {
            $criteria['min_deal_score'] = (float) $search->min_deal_score;
        }
        if (is_numeric($search->min_discount_pct)) {
            $criteria['min_discount_pct'] = (float) $search->min_discount_pct;
        }

        return $criteria;
    }
}
