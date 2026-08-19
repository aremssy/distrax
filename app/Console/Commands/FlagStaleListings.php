<?php

namespace App\Console\Commands;

use App\Models\PropertyListing;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('listings:flag-stale')]
#[Description('Flag stale active listings that owners have not refreshed within the configured freshness window.')]
class FlagStaleListings extends Command
{
    public function handle(): int
    {
        $days = (int) setting('listing_freshness_days', 30);
        $now = now();
        $flagged = 0;
        $rows = [];

        // The stale set is unbounded. chunkById (not chunk) is required: the loop
        // stamps needs_confirmation_at, which is the very column the query filters
        // on, so offset-based paging would skip rows as they drop out of the filter.
        PropertyListing::stale()
            ->whereNull('needs_confirmation_at')
            ->with('owner:id,name,email')
            ->chunkById(200, function ($listings) use (&$flagged, &$rows, $now): void {
                $listings->toQuery()->update(['needs_confirmation_at' => $now]);

                $flagged += $listings->count();

                foreach ($listings as $listing) {
                    $rows[] = [
                        $listing->id,
                        mb_strimwidth($listing->title, 0, 50, '…'),
                        $listing->owner?->email ?? '—',
                        $listing->last_freshness_check_at?->diffForHumans() ?? 'never checked',
                    ];
                }
            });

        if ($flagged === 0) {
            $this->info("No new stale listings found (window: {$days} days).");

            return self::SUCCESS;
        }

        $this->warn("Flagged {$flagged} stale listing(s) (not refreshed in {$days}+ days):");

        $this->table(['ID', 'Title', 'Owner', 'Last Check'], $rows);

        return self::SUCCESS;
    }
}
