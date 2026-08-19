<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a compact global zone hierarchy: a handful of countries → one major city
 * each → real districts, with accurate coordinates so `GET /zones/resolve?lat&lng`
 * and the zone tree work worldwide. Idempotent (keyed on slug). Kept intentionally
 * small — buyers extend with their own markets.
 */
class ZoneSeeder extends Seeder
{
    /**
     * country => [lat, lng, city => [lat, lng, [districts...]]]
     *
     * @var array<string, array{0:float,1:float,2:string,3:float,4:float,5:list<string>}>
     */
    private const MARKETS = [
        'United Arab Emirates' => [24.4539, 54.3773, 'Dubai', 25.2048, 55.2708, ['Downtown', 'Dubai Marina', 'Deira', 'Jumeirah', 'Business Bay']],
        'United Kingdom' => [51.5074, -0.1278, 'London', 51.5074, -0.1278, ['Westminster', 'Camden', 'Shoreditch', 'Greenwich', 'Kensington']],
        'United States' => [40.7128, -74.0060, 'New York', 40.7128, -74.0060, ['Manhattan', 'Brooklyn', 'Queens', 'Harlem', 'Tribeca']],
        'France' => [48.8566, 2.3522, 'Paris', 48.8566, 2.3522, ['Le Marais', 'Montmartre', 'Latin Quarter', 'La Défense', 'Saint-Germain']],
        'Japan' => [35.6762, 139.6503, 'Tokyo', 35.6762, 139.6503, ['Shibuya', 'Shinjuku', 'Minato', 'Ginza', 'Setagaya']],
        'Singapore' => [1.3521, 103.8198, 'Singapore', 1.3521, 103.8198, ['Marina Bay', 'Orchard', 'Sentosa', 'Bugis', 'Tanjong Pagar']],
        'Bangladesh' => [23.6850, 90.3563, 'Dhaka', 23.8103, 90.4125, ['Gulshan', 'Banani', 'Dhanmondi', 'Uttara', 'Mirpur']],
    ];

    public function run(): void
    {
        $countryIndex = 0;

        foreach (self::MARKETS as $countryName => [$cLat, $cLng, $cityName, $lat, $lng, $districts]) {
            $country = $this->zone($countryName, 'country', $cLat, $cLng, null, $countryIndex++);
            $city = $this->zone($cityName, 'city', $lat, $lng, $country->id);

            foreach ($districts as $i => $district) {
                // Spread districts in a small grid around the city centre.
                $aLat = $lat + ((($i % 5) - 2) * 0.012);
                $aLng = $lng + ((intdiv($i, 5) - 1) * 0.012);
                $this->zone($district, 'area', $aLat, $aLng, $city->id, $i, $cityName);
            }
        }

        $this->command->info('Seeded '.Zone::count().' zones ('.count(self::MARKETS).' global cities).');
    }

    private function zone(
        string $name,
        string $type,
        float $lat,
        float $lng,
        ?int $parentId,
        int $sort = 0,
        ?string $cityPrefix = null,
    ): Zone {
        // Unique slug even when area names repeat across cities.
        $slug = Str::slug(($cityPrefix && $type === 'area') ? "{$cityPrefix}-{$name}" : $name);

        // A city-state (e.g. Singapore) shares its name — and therefore its slug — with
        // its country. Disambiguate so the city never hijacks the country row and points
        // its parent at itself.
        if ($type === 'city' && Zone::where('slug', $slug)->where('type', 'country')->exists()) {
            $slug .= '-city';
        }

        return Zone::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => $type,
                'lat' => $lat,
                'lng' => $lng,
                'parent_id' => $parentId,
                'is_active' => true,
                'sort_order' => $sort,
            ],
        );
    }
}
