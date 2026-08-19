<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = json_decode((string) file_get_contents(database_path('data/countries.json')), true);

        $rows = collect($countries)->map(fn (array $country): array => [
            'name' => $country['name'] ?? null,
            'code' => $country['iso2'] ?? null,
            'numeric_code' => $country['numeric_code'] ?? null,
            'phone_code' => $country['phone_code'] ?? null,
            'capital' => $country['capital'] ?? null,
            'currency' => $country['currency'] ?? null,
            'currency_name' => $country['currency_name'] ?? null,
            'currency_symbol' => $country['currency_symbol'] ?? null,
            'native' => $country['native'] ?? null,
            'region' => $country['region'] ?? null,
            'latitude' => $country['latitude'] ?? null,
            'longitude' => $country['longitude'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        collect($rows)->chunk(100)->each(fn ($chunk) => Country::upsert($chunk->all(), ['code']));
    }
}
