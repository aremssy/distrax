<?php

namespace Database\Seeders;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Replaces the random placeholder imagery on every listing with curated, type-matched
 * property photos (so apartments show interiors, not lakes), gives each listing a real
 * photo gallery, and backfills amenities and kitchen/balcony counts.
 *
 * Photos are stable, hot-link-friendly Unsplash URLs stored on the `remote` disk — no
 * files are downloaded. Assignment is deterministic (keyed on the listing id), so a
 * given listing always keeps the same gallery across re-seeds.
 */
class ShowcaseSeeder extends Seeder
{
    /**
     * Curated photo pools per listing type. Each URL is a real Unsplash photo served
     * at a listing-friendly size.
     *
     * @var array<string, array<int, string>>
     */
    private array $photoPools = [
        'rent' => [
            'photo-1522708323590-d24dbb6b0267', // living room
            'photo-1502672260266-1c1ef2d93688', // bright interior
            'photo-1560448204-e02f11c3d0e2',    // modern flat
            'photo-1522771739844-6a9f6d5f14af',  // bedroom
            'photo-1556911220-bff31c812dba',    // kitchen
            'photo-1584622650111-993a426fbf0a',  // living space
        ],
        'sale' => [
            'photo-1568605114967-8130f3a36994', // house exterior
            'photo-1570129477492-45c003edd2be', // suburban house
            'photo-1580587771525-78b9dba3b914', // house facade
            'photo-1512917774080-9991f1c4c750', // large home
            'photo-1600585154340-be6161a56a0c', // modern house
            'photo-1600607687939-ce8a6c25118c', // interior
        ],
        'hotel' => [
            'photo-1566073771259-6a8506099945', // hotel room
            'photo-1611892440504-42a792e24d32', // suite
            'photo-1445019980597-93fa8acb246c', // hotel lobby
            'photo-1590490360182-c33d57733427', // luxury room
            'photo-1582719478250-c89cae4dc85b', // resort
            'photo-1631049307264-da0ec9d70304', // hotel bath
        ],
        'mess' => [
            'photo-1555854877-bab0e564b8d5',    // shared room
            'photo-1595526114035-0d45ed16cfbf', // dorm bed
            'photo-1540518614846-7eded433c457', // bunk room
            'photo-1585412727339-54e4bae3bbf9', // simple room
            'photo-1533779283484-8ad4940aa3a8', // study desk
        ],
        'land' => [
            'photo-1500382017468-9049fed747ef', // open field
            'photo-1628624747186-a941c476b7ef', // plot
            'photo-1416879595882-3373a0480b5b', // green land
            'photo-1501004318641-b39e6451bec6', // countryside
            'photo-1523712999610-f77fbcfc3843', // forest plot
        ],
        'office' => [
            'photo-1497366754035-f200968a6e72', // open office
            'photo-1524758631624-e2822e304c36', // meeting room
            'photo-1497366811353-6870744d04b2', // workspace
            'photo-1600880292203-757bb62b4baf', // modern office
            'photo-1568992687947-868a62a9f521', // desks
        ],
        'room' => [
            'photo-1555854877-bab0e564b8d5',    // shared room
            'photo-1522771739844-6a9f6d5f14af', // private bedroom
            'photo-1540518614846-7eded433c457', // single room
            'photo-1585412727339-54e4bae3bbf9', // simple room
            'photo-1560448204-e02f11c3d0e2',    // furnished room
        ],
    ];

    public function run(): void
    {
        $kitchenFieldId = CustomField::where('key', 'kitchen')->value('id');
        $balconyFieldId = CustomField::where('key', 'balcony')->value('id');

        // The global showcase listings carry curated famous-place covers; leave them
        // untouched so a repeat seed doesn't replace them with generic gallery photos.
        $globalHostId = User::where('email', 'global-host@rentdo.com')->value('id');

        PropertyListing::query()
            ->when($globalHostId, fn ($query) => $query->where('owner_id', '!=', $globalHostId))
            ->orderBy('id')
            ->select(['id', 'type', 'utility_flags', 'bedrooms'])
            ->chunkById(200, function ($listings) use ($kitchenFieldId, $balconyFieldId): void {
                $imageRows = [];
                $customRows = [];
                $now = now();

                foreach ($listings as $listing) {
                    $imageRows = array_merge($imageRows, $this->galleryFor($listing, $now));

                    if ($this->needsAmenities($listing)) {
                        $listing->utility_flags = $this->amenitiesFor($listing);
                        $listing->saveQuietly();
                    }

                    // Kitchen/balcony counts feed the stats row on the detail page.
                    if ($kitchenFieldId && $listing->type !== 'land') {
                        $customRows[] = $this->customRow($listing->id, $kitchenFieldId, (string) $this->kitchenCount($listing), $now);
                    }
                    if ($balconyFieldId && in_array($listing->type, ['rent', 'sale'], true)) {
                        $customRows[] = $this->customRow($listing->id, $balconyFieldId, (string) $this->balconyCount($listing), $now);
                    }
                }

                $listingIds = $listings->pluck('id');

                // Replace imagery wholesale so no random placeholder survives.
                PropertyImage::whereIn('property_listing_id', $listingIds)->delete();
                foreach (array_chunk($imageRows, 500) as $batch) {
                    PropertyImage::insert($batch);
                }

                if ($customRows !== []) {
                    $fieldIds = array_filter([$kitchenFieldId, $balconyFieldId]);
                    CustomFieldValue::whereIn('property_listing_id', $listingIds)
                        ->whereIn('custom_field_id', $fieldIds)
                        ->delete();

                    foreach (array_chunk($customRows, 500) as $batch) {
                        CustomFieldValue::insert($batch);
                    }
                }
            });

        $this->command?->info('Showcase imagery, amenities and room counts applied to all listings.');
    }

    /**
     * A deterministic 4-photo gallery for one listing.
     *
     * @return array<int, array<string, mixed>>
     */
    private function galleryFor(PropertyListing $listing, $now): array
    {
        $pool = $this->photoPools[$listing->type] ?? $this->photoPools['rent'];
        $count = count($pool);
        $start = $listing->id % $count;

        $rows = [];
        $gallerySize = min(4, $count);

        for ($offset = 0; $offset < $gallerySize; $offset++) {
            $photoId = $pool[($start + $offset) % $count];
            $rows[] = [
                'property_listing_id' => $listing->id,
                'path' => 'https://images.unsplash.com/'.$photoId.'?auto=format&fit=crop&w=1200&q=70',
                'disk' => 'remote',
                'is_cover' => $offset === 0,
                'is_floor_plan' => false,
                'sort_order' => $offset,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function customRow(int $listingId, int $fieldId, string $value, $now): array
    {
        return [
            'property_listing_id' => $listingId,
            'custom_field_id' => $fieldId,
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function needsAmenities(PropertyListing $listing): bool
    {
        return empty($listing->utility_flags);
    }

    /**
     * A believable amenity set for the type, drawn from config/amenities.php keys.
     *
     * @return array<int, string>
     */
    private function amenitiesFor(PropertyListing $listing): array
    {
        $base = match ($listing->type) {
            'rent', 'sale' => ['lift', 'security_staff', 'cctv', 'parking_space', 'generator_backup', 'water_supply', 'gas_line', 'internet'],
            'hotel' => ['lift', 'security_staff', 'cctv', 'electricity_backup', 'water_supply', 'internet', 'air_conditioning', 'fire_safety'],
            'mess' => ['security_staff', 'water_supply', 'gas_line', 'internet', 'electricity_backup'],
            'land' => ['water_supply', 'gas_line'],
            default => ['water_supply'],
        };

        // Vary the tail deterministically so listings are not identical.
        $extras = ['fire_safety', 'rooftop_access', 'prayer_room', 'balcony'];
        $picked = $extras[$listing->id % count($extras)];

        return array_values(array_unique([...$base, $picked]));
    }

    private function kitchenCount(PropertyListing $listing): int
    {
        return match ($listing->type) {
            'hotel', 'mess' => 1,
            default => 1 + ($listing->id % 2),
        };
    }

    private function balconyCount(PropertyListing $listing): int
    {
        return ($listing->bedrooms ?? 2) >= 3 ? 2 : 1;
    }
}
