<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('offers:expire')]
#[Description('Automatically expire active offers past their expires_at and notify both parties.')]
class ExpireOffers extends Command
{
    public function handle(NotificationDispatcher $dispatcher): int
    {
        $expired = 0;

        Offer::query()
            ->whereIn('status', ['pending', 'countered'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['listing.owner', 'buyer'])
            ->chunkById(100, function ($offers) use ($dispatcher, &$expired): void {
                foreach ($offers as $offer) {
                    $offer->update(['status' => 'expired']);
                    $expired++;

                    $title = __('Offer expired');
                    $body = __('The offer on :title has expired and is no longer active.', ['title' => $offer->listing->title]);

                    $dispatcher->send($offer->buyer, 'offer_status_change', $title, $body, [], $offer);
                    $dispatcher->send($offer->listing->owner, 'offer_status_change', $title, $body, [], $offer);
                }
            });

        $this->info("Expired {$expired} offer(s).");

        return self::SUCCESS;
    }
}
