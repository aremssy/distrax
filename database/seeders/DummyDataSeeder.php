<?php

namespace Database\Seeders;

use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a compact, international demo set: a few global owners and a modest number
 * of property listings spread across every seeded city, each priced in its city's
 * local currency with a cover image. Bulk-inserted for speed; re-running first
 *
 * clears prior demo data (listings owned by *@demo.test) so the count stays exact.
 *
 * Run: php artisan db:seed --class=DummyDataSeeder
 */
class DummyDataSeeder extends Seeder
{
    /** Total demo listings to generate — kept small and representative. */
    private const TARGET = 60;

    private const TYPES = ['rent', 'sale', 'hotel', 'office', 'room', 'land'];

    private const TENANTS = ['bachelor', 'family', 'both'];

    /**
     * country name => [ISO alpha-2, currency, rent anchor, sale anchor, nightly anchor]
     * Anchors are in the country's own currency (major units).
     *
     * @var array<string, array{0:string,1:string,2:int,3:int,4:int}>
     */
    private const MARKETS = [
        'United States' => ['US', 'USD', 3000, 750000, 220],
        'United Kingdom' => ['GB', 'GBP', 2500, 650000, 180],
        'United Arab Emirates' => ['AE', 'AED', 9000, 1800000, 700],
        'France' => ['FR', 'EUR', 2200, 550000, 190],
        'Japan' => ['JP', 'JPY', 220000, 60000000, 22000],
        'Singapore' => ['SG', 'SGD', 3800, 1200000, 300],
        'Bangladesh' => ['BD', 'BDT', 25000, 8000000, 4000],
    ];

    public function run(): void
    {
        $owners = $this->owners();
        $ownerIds = $owners->pluck('id')->all();

        $areas = Zone::where('type', 'area')->get(['id', 'name', 'lat', 'lng', 'parent_id']);
        if ($areas->isEmpty()) {
            $this->command->warn('No area zones — run ZoneSeeder first.');

            return;
        }

        $countryOf = $this->countryResolver();

        // Clean prior demo data (hard delete → cascades property_images).
        PropertyListing::withTrashed()->whereIn('owner_id', $ownerIds)->forceDelete();

        $now = now();
        $rows = [];
        for ($n = 1; $n <= self::TARGET; $n++) {
            $zone = $areas[($n - 1) % $areas->count()];
            $type = self::TYPES[$n % count(self::TYPES)];

            [$country, $iso, $currency] = $countryOf($zone->parent_id);
            $beds = in_array($type, ['land', 'office'], true) ? null : ($type === 'room' ? 1 : random_int(1, 5));
            $title = $this->title($type, $n, $zone->name);

            $rows[] = [
                'zone_id' => $zone->id,
                'owner_id' => $ownerIds[$n % count($ownerIds)],
                'type' => $type,
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => 'Well-appointed '.$type.' property in '.$zone->name.
                    ' with excellent transport links, shops and amenities nearby. Ready to move in.',
                'language_tag' => 'en',
                'price' => $this->priceFor($type, $country),
                'currency_code' => $currency,
                'service_charge' => $type === 'rent' ? (int) round($this->priceFor('rent', $country) * 0.05) : 0,
                'advance_months' => $type === 'rent' ? random_int(0, 3) : 0,
                'bedrooms' => $beds,
                'bathrooms' => $beds ? random_int(1, $beds) : null,
                'floor' => in_array($type, ['land'], true) ? null : random_int(1, 12),
                'parking' => $beds !== null && $beds >= 2,
                'furnished' => (bool) random_int(0, 1),
                'allowed_for' => self::TENANTS[$n % count(self::TENANTS)],
                'address' => $zone->name.', '.$country,
                'country_code' => $iso,
                'lat' => $zone->lat,
                'lng' => $zone->lng,
                'status' => 'active',
                'is_featured' => $n % 7 === 0,
                'is_verified' => $n % 3 === 0,
                'view_count' => random_int(5, 800),
                'published_at' => $now->copy()->subDays(random_int(0, 120)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            PropertyListing::insert($chunk);
        }

        // Cover images for the freshly inserted demo listings.
        $images = [];
        PropertyListing::whereIn('owner_id', $ownerIds)
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) use (&$images, $now) {
                $images[] = [
                    'property_listing_id' => $id,
                    'path' => 'https://picsum.photos/seed/rb'.$id.'/800/600',
                    'disk' => 'remote',
                    'is_cover' => true,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });
        foreach (array_chunk($images, 500) as $chunk) {
            PropertyImage::insert($chunk);
        }

        $byType = PropertyListing::whereIn('owner_id', $ownerIds)
            ->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type');
        $this->command->info('Seeded '.array_sum($byType->all()).' international listings: '.$byType->toJson());
    }

    /**
     * Build a resolver mapping a city zone id to its [country name, ISO, currency],
     * walking the zone tree once so the listing loop needs no extra queries.
     *
     * @return callable(int|null): array{0:string,1:string,2:string}
     */
    private function countryResolver(): callable
    {
        $zones = Zone::get(['id', 'name', 'type', 'parent_id'])->keyBy('id');

        return function (?int $cityId) use ($zones): array {
            $node = $cityId ? $zones->get($cityId) : null;

            // Guard against a malformed (cyclic) zone tree so resolution can never loop.
            $seen = [];
            while ($node && $node->type !== 'country' && $node->parent_id && ! isset($seen[$node->id])) {
                $seen[$node->id] = true;
                $node = $zones->get($node->parent_id);
            }

            $market = self::MARKETS[$node?->name] ?? self::MARKETS['United States'];

            return [$node?->name ?? 'United States', $market[0], $market[1]];
        };
    }

    private function owners()
    {
        return collect([
            ['name' => 'James Whitfield', 'email' => 'james@demo.test', 'phone' => '+14155550101', 'country_code' => 'US', 'currency' => 'USD', 'verification_status' => 'verified'],
            ['name' => 'Olivia Bennett', 'email' => 'olivia@demo.test', 'phone' => '+442071838750', 'country_code' => 'GB', 'currency' => 'GBP', 'verification_status' => 'verified'],
            ['name' => 'Omar Al Farsi', 'email' => 'omar@demo.test', 'phone' => '+971501234567', 'country_code' => 'AE', 'currency' => 'AED', 'verification_status' => 'unverified'],
            ['name' => 'Camille Laurent', 'email' => 'camille@demo.test', 'phone' => '+33612345678', 'country_code' => 'FR', 'currency' => 'EUR', 'verification_status' => 'verified'],
            ['name' => 'Haruto Tanaka', 'email' => 'haruto@demo.test', 'phone' => '+819012345678', 'country_code' => 'JP', 'currency' => 'JPY', 'verification_status' => 'verified'],
            ['name' => 'Mei Lin', 'email' => 'mei@demo.test', 'phone' => '+6591234567', 'country_code' => 'SG', 'currency' => 'SGD', 'verification_status' => 'verified'],
            ['name' => 'Rahim Uddin', 'email' => 'rahim@demo.test', 'phone' => '+8801711000001', 'country_code' => 'BD', 'currency' => 'BDT', 'verification_status' => 'verified'],
        ])->map(fn ($o) => User::firstOrCreate(
            ['email' => $o['email']],
            array_merge($o, [
                'password' => Hash::make('password'),
                'language' => 'en',
                'phone_visibility' => 'registered',
            ]),
        ));
    }

    private function title(string $type, int $n, string $zone): string
    {
        $label = match ($type) {
            'rent' => 'Rental apartment',
            'sale' => 'Apartment for sale',
            'hotel' => 'Short-stay suite',
            'office' => 'Office space',
            'room' => 'Private room',
            'land' => 'Land plot',
            default => ucfirst($type).' property',
        };

        return "{$label} #{$n} in {$zone}";
    }

    /**
     * A realistic price in the country's own currency, scaled by listing type.
     */
    private function priceFor(string $type, string $country): int
    {
        [, , $rent, $sale, $night] = self::MARKETS[$country] ?? self::MARKETS['United States'];

        return match ($type) {
            'sale', 'land' => (int) round($sale * random_int(60, 160) / 100),
            'hotel', 'vacation' => (int) round($night * random_int(70, 140) / 100),
            default => (int) round($rent * random_int(60, 150) / 100),
        };
    }
}
