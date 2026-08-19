<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Global currency set. `exchange_rate` is the value of ONE unit of the currency in
 * the platform base currency (USD here, the row with is_default = true), so the base
 * always sits at 1.0. Rates below are a reasonable snapshot; the scheduled
 * RefreshExchangeRates command keeps them current in production.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->currencies() as $sort => $data) {
            Currency::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['sort_order' => $sort]),
            );
        }

        Currency::flushCache();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currencies(): array
    {
        // code, name, symbol, symbol_position, decimals, thousands, decimal, rate(USD per unit)
        $rows = [
            ['USD', 'US Dollar', '$', 'before', 2, ',', '.', 1.000000],
            ['EUR', 'Euro', '€', 'before', 2, '.', ',', 1.080000],
            ['GBP', 'British Pound', '£', 'before', 2, ',', '.', 1.270000],
            ['AED', 'UAE Dirham', 'د.إ', 'before', 2, ',', '.', 0.272000],
            ['SAR', 'Saudi Riyal', '﷼', 'before', 2, ',', '.', 0.266000],
            ['AUD', 'Australian Dollar', 'A$', 'before', 2, ',', '.', 0.660000],
            ['CAD', 'Canadian Dollar', 'C$', 'before', 2, ',', '.', 0.730000],
            ['SGD', 'Singapore Dollar', 'S$', 'before', 2, ',', '.', 0.740000],
            ['JPY', 'Japanese Yen', '¥', 'before', 0, ',', '.', 0.006400],
            ['CNY', 'Chinese Yuan', '¥', 'before', 2, ',', '.', 0.140000],
            ['INR', 'Indian Rupee', '₹', 'before', 2, ',', '.', 0.012000],
            ['MYR', 'Malaysian Ringgit', 'RM', 'before', 2, ',', '.', 0.210000],
            ['ZAR', 'South African Rand', 'R', 'before', 2, ' ', '.', 0.053000],
            ['BRL', 'Brazilian Real', 'R$', 'before', 2, '.', ',', 0.200000],
            ['TRY', 'Turkish Lira', '₺', 'before', 2, '.', ',', 0.031000],
            ['BDT', 'Bangladeshi Taka', '৳', 'before', 2, ',', '.', 0.009100],
            ['PKR', 'Pakistani Rupee', '₨', 'before', 2, ',', '.', 0.003600],
            ['NGN', 'Nigerian Naira', '₦', 'before', 2, ',', '.', 0.000650],
        ];

        return array_map(fn ($r) => [
            'code' => $r[0],
            'name' => $r[1],
            'symbol' => $r[2],
            'symbol_position' => $r[3],
            'decimal_places' => $r[4],
            'thousands_separator' => $r[5],
            'decimal_separator' => $r[6],
            'exchange_rate' => $r[7],
            'is_default' => $r[0] === 'USD',
            'is_active' => true,
        ], $rows);
    }
}
