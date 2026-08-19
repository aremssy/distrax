<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\TechnicianCategory;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds technician_categories, technician users, technicians,
 * technician_bookings and quotes with realistic, linked demo data.
 *
 * Run: php artisan db:seed --class=TechnicianDemoSeeder
 */
class TechnicianDemoSeeder extends Seeder
{
    private const CATEGORY_NAMES = [
        'Electrician', 'Plumber', 'AC Repair', 'Carpenter',
        'Painter', 'Appliance Repair', 'Pest Control', 'Home Cleaning',
    ];

    private const SKILLS_BY_CATEGORY = [
        'Electrician' => ['Wiring', 'Circuit breakers', 'Lighting installation'],
        'Plumber' => ['Pipe fitting', 'Leak repair', 'Drain cleaning'],
        'AC Repair' => ['Gas refill', 'Compressor repair', 'Installation'],
        'Carpenter' => ['Furniture repair', 'Cabinet making', 'Door fitting'],
        'Painter' => ['Wall painting', 'Waterproofing', 'Texture finish'],
        'Appliance Repair' => ['Washing machine', 'Refrigerator', 'Microwave'],
        'Pest Control' => ['Termite treatment', 'Cockroach control', 'Rodent control'],
        'Home Cleaning' => ['Deep cleaning', 'Sofa cleaning', 'Sanitization'],
    ];

    private const BOOKING_STATUSES = ['pending', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled'];

    public function run(): void
    {
        $now = now();

        $categories = $this->seedCategories($now);
        $technicianUsers = $this->seedTechnicianUsers();
        $technicianIds = $this->seedTechnicians($technicianUsers, $categories, $now);
        $customerIds = $this->seedCustomerUsers();
        $bookingIds = $this->seedBookings($technicianIds, $customerIds, $now);
        $this->seedQuotes($bookingIds, $technicianIds, $now);

        $this->command->info('Seeded '.count($categories).' technician categories, '.count($technicianIds).
            ' technicians, '.count($bookingIds).' technician bookings.');
    }

    /** @return array<int, int> category ids keyed by name */
    private function seedCategories($now): array
    {
        $categoryIds = TechnicianCategory::whereIn('name', self::CATEGORY_NAMES)->pluck('id');

        // Delete dependents first — technicians.technician_category_id is ON DELETE RESTRICT.
        Technician::withTrashed()->whereIn('technician_category_id', $categoryIds)->forceDelete();
        TechnicianCategory::whereIn('name', self::CATEGORY_NAMES)->delete();

        $rows = [];
        foreach (self::CATEGORY_NAMES as $i => $name) {
            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'icon' => null,
                'commission_rate' => random_int(5, 20),
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        TechnicianCategory::insert($rows);

        return TechnicianCategory::whereIn('name', self::CATEGORY_NAMES)->pluck('id', 'name')->all();
    }

    /** @return array<int> user ids for the 15 demo technician accounts */
    private function seedTechnicianUsers(): array
    {
        $ids = [];
        for ($i = 1; $i <= 15; $i++) {
            $user = User::firstOrCreate(
                ['email' => "technician{$i}@demo.test"],
                [
                    'name' => fake()->name(),
                    'phone' => '+1415'.random_int(2000000, 9999999),
                    'password' => Hash::make('password'),
                    'verification_status' => $i % 4 === 0 ? 'pending' : 'verified',
                    'language' => 'en',
                    'currency' => 'USD',
                    'phone_visibility' => 'registered',
                ]
            );
            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * @param  array<int>  $userIds
     * @param  array<string, int>  $categories
     * @return array<int>
     */
    private function seedTechnicians(array $userIds, array $categories, $now): array
    {
        Technician::whereIn('user_id', $userIds)->forceDelete();

        $zoneIds = Zone::where('type', 'area')->pluck('id')->all();
        $categoryNames = array_keys($categories);
        $userNames = User::whereIn('id', $userIds)->pluck('name', 'id');

        $rows = [];
        foreach ($userIds as $i => $userId) {
            $categoryName = $categoryNames[$i % count($categoryNames)];
            $statuses = ['active', 'active', 'active', 'pending', 'inactive'];

            $rows[] = [
                'user_id' => $userId,
                'slug' => Str::slug(($userNames[$userId] ?? 'technician').'-'.$userId),
                'technician_category_id' => $categories[$categoryName],
                'zone_id' => $zoneIds[$i % count($zoneIds)],
                'bio' => "Experienced {$categoryName} technician offering reliable, on-time service.",
                'skills' => json_encode(self::SKILLS_BY_CATEGORY[$categoryName]),
                'available_time' => json_encode(['morning', 'afternoon', 'evening'][random_int(0, 2)] === 'morning'
                    ? ['morning', 'evening']
                    : ['afternoon', 'evening']),
                'experience_years' => random_int(1, 15),
                'hourly_rate' => random_int(300, 3000),
                'rating' => number_format(random_int(30, 50) / 10, 2, '.', ''),
                'is_available' => $i % 5 !== 4,
                'is_verified' => $i % 4 !== 0,
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Technician::insert($rows);

        return Technician::whereIn('user_id', $userIds)->pluck('id')->all();
    }

    /** @return array<int> */
    private function seedCustomerUsers(): array
    {
        $ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::firstOrCreate(
                ['email' => "customer{$i}@demo.test"],
                [
                    'name' => fake()->name(),
                    'phone' => '+4420'.random_int(20000000, 89999999),
                    'password' => Hash::make('password'),
                    'verification_status' => 'verified',
                    'language' => 'en',
                    'currency' => 'USD',
                    'phone_visibility' => 'everyone',
                ]
            );
            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * @param  array<int>  $technicianIds
     * @param  array<int>  $customerIds
     * @return array<int>
     */
    private function seedBookings(array $technicianIds, array $customerIds, $now): array
    {
        TechnicianBooking::whereIn('user_id', $customerIds)->forceDelete();

        $rows = [];
        for ($n = 1; $n <= 25; $n++) {
            $status = self::BOOKING_STATUSES[$n % count(self::BOOKING_STATUSES)];
            $agreed = in_array($status, ['accepted', 'in_progress', 'completed'], true)
                ? random_int(500, 8000)
                : null;

            $rows[] = [
                'technician_id' => $technicianIds[$n % count($technicianIds)],
                'user_id' => $customerIds[$n % count($customerIds)],
                'description' => 'Need help with a '.fake()->words(3, true).' issue at home.',
                'scheduled_at' => $now->copy()->addDays(random_int(-10, 15)),
                'agreed_amount' => $agreed,
                'is_urgent' => $n % 6 === 0,
                'status' => $status,
                'address' => fake()->address(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        TechnicianBooking::insert($rows);

        return TechnicianBooking::whereIn('user_id', $customerIds)->pluck('id')->all();
    }

    /**
     * @param  array<int>  $bookingIds
     * @param  array<int>  $technicianIds
     */
    private function seedQuotes(array $bookingIds, array $technicianIds, $now): void
    {
        Quote::whereIn('technician_booking_id', $bookingIds)->delete();

        $statuses = ['pending', 'accepted', 'rejected', 'expired'];
        $rows = [];
        foreach ($bookingIds as $i => $bookingId) {
            // Not every booking gets a quote yet (some are still 'pending').
            if ($i % 3 === 0) {
                continue;
            }

            $rows[] = [
                'technician_booking_id' => $bookingId,
                'technician_id' => $technicianIds[$i % count($technicianIds)],
                'amount' => random_int(500, 8000),
                'description' => 'Quote covering parts and labour for the reported issue.',
                'valid_until' => $now->copy()->addDays(3),
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Quote::insert($rows);
    }
}
