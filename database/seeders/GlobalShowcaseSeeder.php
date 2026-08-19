<?php

namespace Database\Seeders;

use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A small, global demo set: one showcase listing in each of a handful of famous
 * cities, each priced in its own local currency, with a bundled famous-place photo.
 * Deliberately minimal — it exists to show the marketplace working worldwide, not
 * to fill the database. Idempotent (keyed on slug).
 */
class GlobalShowcaseSeeder extends Seeder
{
    private const IMAGE_SOURCE_DIR = 'assets/images/website/listings/global';

    private const STORAGE_DIR = 'listings/seeded/global';

    /** Country name => ISO 3166-1 alpha-2. */
    private const COUNTRY_ISO = [
        'United Arab Emirates' => 'AE',
        'United Kingdom' => 'GB',
        'United States' => 'US',
        'France' => 'FR',
        'Japan' => 'JP',
        'Singapore' => 'SG',
    ];

    /**
     * A mix of listing types so the showcase exercises the whole marketplace:
     * bookable stays (hotel, per-night), long-term homes (rent, per-month) and a
     * sale. Price is in each city's own currency at the scale its type implies.
     *
     * image key => [country, city, lat, lng, currency, type, price, title, beds, baths, sqft]
     *
     * @var array<string, array{0:string,1:string,2:float,3:float,4:string,5:string,6:int,7:string,8:int,9:int,10:int}>
     */
    private const CITIES = [
        'dubai' => ['United Arab Emirates', 'Dubai', 25.1972, 55.2744, 'AED', 'hotel', 900, 'Downtown Suite with Burj Khalifa View', 2, 2, 1350],
        'london' => ['United Kingdom', 'London', 51.5055, -0.0754, 'GBP', 'rent', 3200, 'Riverside Flat beside Tower Bridge', 1, 1, 720],
        'newyork' => ['United States', 'New York', 40.7484, -73.9857, 'USD', 'sale', 1250000, 'Midtown Manhattan Skyline Condo', 2, 2, 1100],
        'paris' => ['France', 'Paris', 48.8584, 2.2945, 'EUR', 'hotel', 320, 'Apartment with Eiffel Tower View', 1, 1, 650],
        'tokyo' => ['Japan', 'Tokyo', 35.6586, 139.7454, 'JPY', 'rent', 320000, 'Minato Tower Residence near Tokyo Tower', 1, 1, 600],
        'singapore' => ['Singapore', 'Singapore', 1.2834, 103.8607, 'SGD', 'hotel', 480, 'Marina Bay Skyline Suite', 2, 2, 980],
    ];

    public function run(): void
    {
        $owner = $this->showcaseOwner();

        foreach (self::CITIES as $key => [$country, $city, $lat, $lng, $currency, $type, $price, $title, $beds, $baths, $sqft]) {
            $zone = $this->cityZone($country, $city, $lat, $lng);

            $listing = PropertyListing::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'zone_id' => $zone->id,
                    'owner_id' => $owner->id,
                    'type' => $type,
                    'title' => $title,
                    'description' => "A curated global showcase home in {$city}. Walking distance to the city's most famous landmark, fully furnished and move-in ready.",
                    'price' => $price,
                    'currency_code' => $currency,
                    'country_code' => self::COUNTRY_ISO[$country] ?? null,
                    'bedrooms' => $beds,
                    'bathrooms' => $baths,
                    'area_sqft' => $sqft,
                    'furnished' => true,
                    'allowed_for' => 'both',
                    'address' => "{$city}, {$country}",
                    'lat' => $lat,
                    'lng' => $lng,
                    'status' => 'active',
                    'is_featured' => true,
                    'is_verified' => true,
                    'published_at' => now(),
                ],
            );

            $this->attachCover($listing, $key);
        }
    }

    private function showcaseOwner(): User
    {
        return User::where('email', 'global-host@rentdo.com')->first()
            ?? User::factory()->create([
                'name' => 'Rentdo Global Host',
                'email' => 'global-host@rentdo.com',
                'verification_status' => 'verified',
                'email_verified_at' => now(),
            ]);
    }

    private function cityZone(string $country, string $city, float $lat, float $lng): Zone
    {
        $countryZone = Zone::updateOrCreate(
            ['slug' => Str::slug($country)],
            ['name' => $country, 'type' => 'country', 'lat' => $lat, 'lng' => $lng, 'parent_id' => null, 'is_active' => true],
        );

        // A city whose slug collides with ANY country row (e.g. the city-state Singapore)
        // would otherwise hijack that country via updateOrCreate and reparent it onto
        // itself. Disambiguate against every country, matching ZoneSeeder's guard.
        $citySlug = Str::slug($city);
        if (Zone::where('slug', $citySlug)->where('type', 'country')->exists()) {
            $citySlug .= '-city';
        }

        return Zone::updateOrCreate(
            ['slug' => $citySlug],
            ['name' => $city, 'type' => 'city', 'lat' => $lat, 'lng' => $lng, 'parent_id' => $countryZone->id, 'is_active' => true],
        );
    }

    private function attachCover(PropertyListing $listing, string $key): void
    {
        $source = public_path(self::IMAGE_SOURCE_DIR."/{$key}.webp");

        if (! File::exists($source)) {
            return;
        }

        $storedPath = self::STORAGE_DIR."/{$key}.webp";
        Storage::disk('public')->put($storedPath, File::get($source));

        PropertyImage::updateOrCreate(
            ['property_listing_id' => $listing->id, 'is_cover' => true],
            ['path' => $storedPath, 'disk' => 'public', 'is_floor_plan' => false, 'sort_order' => 0],
        );
    }
}
