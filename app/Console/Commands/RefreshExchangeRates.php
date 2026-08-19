<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pulls live FX rates for every active currency and writes them in the platform's
 * own convention: exchange_rate = value of one unit of the currency in the base
 * currency. The provider returns "units of X per 1 base", so each rate is inverted.
 *
 * The base currency stays pinned at 1.0 and any currency flagged rate_locked is left
 * exactly as the admin set it.
 */
#[Signature('currency:refresh-rates')]
#[Description('Refresh currency exchange rates from the live provider feed.')]
class RefreshExchangeRates extends Command
{
    public function handle(): int
    {
        $base = Currency::defaultActive();

        if (! $base) {
            $this->error('No default (base) currency is configured.');

            return self::FAILURE;
        }

        $url = rtrim((string) config('services.exchange_rates.url', 'https://open.er-api.com/v6/latest'), '/');

        $response = Http::timeout(15)->retry(2, 200)->get("{$url}/{$base->code}");

        if (! $response->ok() || ! is_array($rates = $response->json('rates'))) {
            $this->error("Rate provider request failed ({$response->status()}).");

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = [];

        foreach (Currency::where('is_active', true)->get() as $currency) {
            if ($currency->is_default) {
                $currency->update(['exchange_rate' => 1.000000]);

                continue;
            }

            if ($currency->rate_locked) {
                $skipped[] = $currency->code;

                continue;
            }

            $perBase = $rates[$currency->code] ?? null;

            if (! is_numeric($perBase) || (float) $perBase <= 0) {
                $skipped[] = $currency->code;

                continue;
            }

            // Provider gives "currency per 1 base"; we store "base per 1 currency".
            $currency->update(['exchange_rate' => round(1 / (float) $perBase, 6)]);
            $updated++;
        }

        Currency::flushCache();

        $this->info("Refreshed {$updated} rate(s) against base {$base->code}.".
            ($skipped ? ' Skipped: '.implode(', ', $skipped).'.' : ''));

        return self::SUCCESS;
    }
}
