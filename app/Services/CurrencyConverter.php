<?php

namespace App\Services;

use App\Models\Currency;

/**
 * Converts money between the platform's active currencies.
 *
 * Every currency stores `exchange_rate` = the value of one of its units in the base
 * currency (the row flagged is_default, whose rate is therefore 1.0). Converting is
 * a two-hop through the base: A -> base -> B, so the base currency never has to be
 * hard-coded and any pair works.
 */
class CurrencyConverter
{
    /**
     * Convert an amount from one currency to another, rounded to the target
     * currency's decimal places. Unknown/inactive codes fall back to the base
     * currency so a bad code never throws mid-checkout.
     */
    public function convert(float|int $amount, ?string $from, ?string $to): float
    {
        $fromRate = $this->rate($from);
        $toRate = $this->rate($to);

        if ($fromRate <= 0.0 || $toRate <= 0.0) {
            return (float) $amount;
        }

        $converted = ((float) $amount * $fromRate) / $toRate;

        return round($converted, $this->decimals($to));
    }

    /**
     * Convert to the platform base currency (accounting / reporting currency).
     */
    public function toBase(float|int $amount, ?string $from): float
    {
        return $this->convert($amount, $from, $this->baseCode());
    }

    public function baseCode(): string
    {
        return Currency::defaultActive()?->code ?? 'USD';
    }

    private function rate(?string $code): float
    {
        $currency = Currency::findActiveByCode($code) ?? Currency::defaultActive();

        return (float) ($currency?->exchange_rate ?? 0.0);
    }

    private function decimals(?string $code): int
    {
        $currency = Currency::findActiveByCode($code) ?? Currency::defaultActive();

        return (int) ($currency?->decimal_places ?? 2);
    }
}
