<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\AvailabilityCalendar;
use App\Models\Block;
use App\Models\Compare;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Device;
use App\Models\Favorite;
use App\Models\HotelBooking;
use App\Models\ListingPackage;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\PropertyVideo;
use App\Models\Report;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Models\SubscriptionPlan;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserSubscription;
use App\Models\VisitSchedule;
use App\Models\Wallet;
use App\Models\Zone;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds agencies, agents, property media, reviews, favorites/compares,
 * messaging, notifications, devices, saved searches, hotel bookings, visit
 * schedules, availability calendar, maintenance requests, custom fields,
 * coupons, subscription plans/packages, payments, wallet transactions,
 * blocks and reports — all wired to the real demo users/listings/
 * technicians produced by DummyDataSeeder and TechnicianDemoSeeder.
 *
 * Run: php artisan db:seed --class=MarketplaceDemoSeeder
 */
class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $listingIds = PropertyListing::orderBy('id')->pluck('id')->all();
        $hotelListingIds = PropertyListing::where('type', 'hotel')->pluck('id')->all();
        $ownerIds = PropertyListing::distinct()->pluck('owner_id')->all();
        $technicianRecords = Technician::pluck('id', 'user_id');
        $technicianIds = $technicianRecords->values()->all();
        $customerIds = User::where('email', 'like', 'customer%@demo.test')->pluck('id')->all();
        $allUserIds = User::pluck('id')->all();
        $zoneIds = Zone::pluck('id')->all();

        if (empty($listingIds) || empty($ownerIds)) {
            $this->command->warn('No property listings found — run DummyDataSeeder first.');

            return;
        }

        $agencyIds = $this->seedAgencies($ownerIds, $zoneIds, $now);
        $this->seedAgents($agencyIds, $now);
        $this->seedPropertyVideos($listingIds, $now);
        $this->seedReviews($allUserIds, $listingIds, $technicianIds, $agencyIds, $now);
        $this->seedFavoritesAndCompares($customerIds, $listingIds, $now);
        $this->seedConversationsAndMessages($customerIds, $ownerIds, $listingIds, $now);
        $this->seedUserNotifications($allUserIds, $listingIds, $now);
        $this->seedDevices($allUserIds, $now);
        $this->seedSavedSearches($customerIds, $now);
        $this->seedHotelBookings($hotelListingIds, $customerIds, $now);
        $this->seedVisitSchedules($listingIds, $customerIds, $now);
        $this->seedAvailabilityCalendar($hotelListingIds, $now);
        $this->seedMaintenanceRequests($customerIds, $ownerIds, $technicianRecords, $listingIds, $now);
        $customFieldIds = $this->seedCustomFields($now);
        $this->seedCustomFieldValues($customFieldIds, $listingIds, $now);
        $couponIds = $this->seedCoupons($now);
        $planIds = $this->seedSubscriptionPlans($now);
        $userSubscriptionIds = $this->seedUserSubscriptions($ownerIds, $planIds, $now);
        $this->seedListingPackages($now);
        $this->seedPayments($allUserIds, $userSubscriptionIds, $couponIds, $now);
        $this->seedWalletTransactions($allUserIds);
        $this->seedBlocks($allUserIds, $now);
        $this->seedReports($allUserIds, $listingIds, $technicianIds, $now);

        $this->command->info('Marketplace demo data seeded.');
    }

    /** @return array<int> */
    private function seedAgencies(array $ownerIds, array $zoneIds, $now): array
    {
        $targetOwners = array_slice($ownerIds, 0, 8);
        Agency::whereIn('owner_id', $targetOwners)->forceDelete();

        $rows = [];
        foreach ($targetOwners as $i => $ownerId) {
            $name = fake()->company().' Realty';
            $rows[] = [
                'owner_id' => $ownerId,
                'zone_id' => $zoneIds[$i % count($zoneIds)],
                'name' => $name,
                'slug' => Str::slug($name).'-'.($i + 1),
                'logo' => null,
                'description' => 'Trusted real estate agency serving the local community for over '.random_int(2, 15).' years.',
                'phone' => '+1415'.random_int(2000000, 9999999),
                'email' => 'agency'.($i + 1).'@demo.test',
                'website' => null,
                'address' => fake()->address(),
                'rating' => number_format(random_int(30, 50) / 10, 2, '.', ''),
                'is_verified' => $i % 2 === 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Agency::insert($rows);

        return Agency::whereIn('owner_id', $targetOwners)->pluck('id')->all();
    }

    private function seedAgents(array $agencyIds, $now): void
    {
        if (empty($agencyIds)) {
            return;
        }

        Agent::whereIn('agency_id', $agencyIds)->delete();

        $rows = [];
        for ($i = 1; $i <= 15; $i++) {
            $user = User::firstOrCreate(
                ['email' => "agent{$i}@demo.test"],
                [
                    'name' => fake()->name(),
                    'phone' => '+4420'.random_int(20000000, 89999999),
                    'password' => Hash::make('password'),
                    'verification_status' => 'verified',
                    'language' => 'en',
                    'currency' => 'USD',
                    'phone_visibility' => 'registered',
                ]
            );

            $rows[] = [
                'agency_id' => $agencyIds[($i - 1) % count($agencyIds)],
                'user_id' => $user->id,
                'slug' => Str::slug($user->name.'-'.$user->id),
                'title' => fake()->randomElement(['Sales Agent', 'Senior Agent', 'Leasing Consultant']),
                'is_active' => $i % 6 !== 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Agent::insert($rows);
    }

    private function seedPropertyVideos(array $listingIds, $now): void
    {
        $targets = array_slice($listingIds, 0, 20);
        PropertyVideo::whereIn('property_listing_id', $targets)->delete();

        $rows = [];
        foreach ($targets as $listingId) {
            $rows[] = [
                'property_listing_id' => $listingId,
                'url' => 'https://www.youtube.com/watch?v=demo'.$listingId,
                'source' => 'youtube',
                'thumbnail' => 'https://picsum.photos/seed/vid'.$listingId.'/400/225',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        PropertyVideo::insert($rows);
    }

    private function seedReviews(array $userIds, array $listingIds, array $technicianIds, array $agencyIds, $now): void
    {
        Review::withTrashed()->forceDelete();

        $reviewables = [];
        foreach (array_slice($listingIds, 0, 15) as $id) {
            $reviewables[] = [PropertyListing::class, $id];
        }
        foreach (array_slice($technicianIds, 0, 8) as $id) {
            $reviewables[] = [Technician::class, $id];
        }
        foreach (array_slice($agencyIds, 0, 5) as $id) {
            $reviewables[] = [Agency::class, $id];
        }

        $rows = [];
        foreach ($reviewables as $i => [$type, $id]) {
            $rows[] = [
                'reviewer_id' => $userIds[$i % count($userIds)],
                'reviewable_type' => $type,
                'reviewable_id' => $id,
                'rating' => random_int(2, 5),
                'body' => fake()->sentence(random_int(8, 20)),
                'is_verified' => $i % 3 === 0,
                'is_visible' => true,
                'owner_reply' => $i % 4 === 0 ? 'Thank you for your feedback!' : null,
                'owner_replied_at' => $i % 4 === 0 ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Review::insert($rows);
    }

    private function seedFavoritesAndCompares(array $customerIds, array $listingIds, $now): void
    {
        if (empty($customerIds)) {
            return;
        }

        Favorite::whereIn('user_id', $customerIds)->delete();
        Compare::whereIn('user_id', $customerIds)->delete();

        $favoriteRows = [];
        $compareRows = [];
        for ($n = 0; $n < 20; $n++) {
            $favoriteRows[] = [
                'user_id' => $customerIds[$n % count($customerIds)],
                'property_listing_id' => $listingIds[$n % count($listingIds)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        for ($n = 0; $n < 15; $n++) {
            $compareRows[] = [
                'user_id' => $customerIds[$n % count($customerIds)],
                'property_listing_id' => $listingIds[($n + 30) % count($listingIds)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Favorite::insert($favoriteRows);
        Compare::insert($compareRows);
    }

    private function seedConversationsAndMessages(array $customerIds, array $ownerIds, array $listingIds, $now): void
    {
        if (empty($customerIds)) {
            return;
        }

        Conversation::whereIn('starter_id', $customerIds)->forceDelete();

        $conversationRows = [];
        for ($n = 0; $n < 15; $n++) {
            $conversationRows[] = [
                'property_listing_id' => $listingIds[$n % count($listingIds)],
                'starter_id' => $customerIds[$n % count($customerIds)],
                'recipient_id' => $ownerIds[$n % count($ownerIds)],
                'last_message_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Conversation::insert($conversationRows);

        $conversations = Conversation::whereIn('starter_id', $customerIds)
            ->orderByDesc('id')->limit(15)->get(['id', 'starter_id', 'recipient_id']);

        $messageRows = [];
        foreach ($conversations as $conversation) {
            $exchanges = random_int(2, 4);
            for ($m = 0; $m < $exchanges; $m++) {
                $sender = $m % 2 === 0 ? $conversation->starter_id : $conversation->recipient_id;
                $messageRows[] = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender,
                    'body' => fake()->sentence(random_int(6, 15)),
                    'is_phone_revealed' => false,
                    'read_at' => $m < $exchanges - 1 ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        Message::insert($messageRows);
    }

    private function seedUserNotifications(array $userIds, array $listingIds, $now): void
    {
        UserNotification::query()->delete();

        $types = ['listing_approved', 'new_message', 'price_drop', 'booking_confirmed', 'review_received'];
        $rows = [];
        for ($n = 0; $n < 20; $n++) {
            $type = $types[$n % count($types)];
            $rows[] = [
                'user_id' => $userIds[$n % count($userIds)],
                'type' => $type,
                'title' => ucwords(str_replace('_', ' ', $type)),
                'body' => 'Update regarding your account activity on Rentdo.',
                'data' => json_encode(['listing_id' => $listingIds[$n % count($listingIds)]]),
                'is_read' => $n % 3 === 0,
                'read_at' => $n % 3 === 0 ? $now : null,
                'notifiable_type' => PropertyListing::class,
                'notifiable_id' => $listingIds[$n % count($listingIds)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        UserNotification::insert($rows);
    }

    private function seedDevices(array $userIds, $now): void
    {
        Device::query()->delete();

        $platforms = ['android', 'ios', 'web'];
        $rows = [];
        for ($n = 0; $n < 15; $n++) {
            $rows[] = [
                'user_id' => $userIds[$n % count($userIds)],
                'token' => Str::random(64),
                'platform' => $platforms[$n % count($platforms)],
                'name' => fake()->randomElement(['Chrome on Windows', 'Safari on iPhone', 'App on Android']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Device::insert($rows);
    }

    private function seedSavedSearches(array $customerIds, $now): void
    {
        if (empty($customerIds)) {
            return;
        }

        SavedSearch::whereIn('user_id', $customerIds)->delete();

        $rows = [];
        for ($n = 0; $n < 12; $n++) {
            $rows[] = [
                'user_id' => $customerIds[$n % count($customerIds)],
                'name' => 'Search #'.($n + 1),
                'criteria' => json_encode([
                    'type' => ['rent', 'sale', 'hotel'][$n % 3],
                    'min_price' => 5000,
                    'max_price' => 50000,
                ]),
                'alert_on' => $n % 2 === 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        SavedSearch::insert($rows);
    }

    private function seedHotelBookings(array $hotelListingIds, array $customerIds, $now): void
    {
        if (empty($hotelListingIds) || empty($customerIds)) {
            return;
        }

        HotelBooking::whereIn('property_listing_id', $hotelListingIds)->forceDelete();

        $statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'refunded'];
        $rows = [];
        for ($n = 0; $n < 15; $n++) {
            $checkIn = $now->copy()->addDays(random_int(-10, 20));
            $status = $statuses[$n % count($statuses)];
            $rows[] = [
                'property_listing_id' => $hotelListingIds[$n % count($hotelListingIds)],
                'user_id' => $customerIds[$n % count($customerIds)],
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkIn->copy()->addDays(random_int(1, 5))->toDateString(),
                'guests' => random_int(1, 4),
                'amount' => random_int(3000, 40000),
                'status' => $status,
                'special_requests' => $n % 4 === 0 ? 'Late check-in requested.' : null,
                'cancelled_at' => $status === 'cancelled' ? $now : null,
                'refunded_amount' => $status === 'refunded' ? random_int(1000, 5000) : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        HotelBooking::insert($rows);
    }

    private function seedVisitSchedules(array $listingIds, array $customerIds, $now): void
    {
        if (empty($customerIds)) {
            return;
        }

        VisitSchedule::whereIn('user_id', $customerIds)->delete();

        $statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        $rows = [];
        for ($n = 0; $n < 15; $n++) {
            $rows[] = [
                'property_listing_id' => $listingIds[$n % count($listingIds)],
                'user_id' => $customerIds[$n % count($customerIds)],
                'scheduled_at' => $now->copy()->addDays(random_int(-5, 10)),
                'status' => $statuses[$n % count($statuses)],
                'note' => $n % 3 === 0 ? 'Please call before arriving.' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        VisitSchedule::insert($rows);
    }

    private function seedAvailabilityCalendar(array $hotelListingIds, $now): void
    {
        if (empty($hotelListingIds)) {
            return;
        }

        $targets = array_slice($hotelListingIds, 0, 5);
        AvailabilityCalendar::whereIn('property_listing_id', $targets)->delete();

        $rows = [];
        foreach ($targets as $listingId) {
            for ($d = 0; $d < 6; $d++) {
                $rows[] = [
                    'property_listing_id' => $listingId,
                    'date' => $now->copy()->addDays($d)->toDateString(),
                    'is_available' => $d % 4 !== 0,
                    'override_price' => $d % 3 === 0 ? random_int(4000, 9000) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        AvailabilityCalendar::insert($rows);
    }

    /**
     * @param  Collection<int, int>  $technicianRecords  technician id keyed by user_id
     */
    private function seedMaintenanceRequests(array $customerIds, array $ownerIds, $technicianRecords, array $listingIds, $now): void
    {
        if (empty($customerIds)) {
            return;
        }

        MaintenanceRequest::whereIn('tenant_id', $customerIds)->forceDelete();

        $technicianIds = $technicianRecords->values()->all();
        $statuses = ['open', 'assigned', 'in_progress', 'completed', 'closed'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        $rows = [];
        for ($n = 0; $n < 12; $n++) {
            $status = $statuses[$n % count($statuses)];
            $rows[] = [
                'tenant_id' => $customerIds[$n % count($customerIds)],
                'owner_id' => $ownerIds[$n % count($ownerIds)],
                'technician_id' => ($status === 'open' || empty($technicianIds)) ? null : $technicianIds[$n % count($technicianIds)],
                'property_listing_id' => $listingIds[$n % count($listingIds)],
                'title' => fake()->randomElement(['Leaking faucet', 'AC not cooling', 'Broken window', 'Power outage in unit']),
                'description' => fake()->sentence(15),
                'priority' => $priorities[$n % count($priorities)],
                'status' => $status,
                'resolved_at' => in_array($status, ['completed', 'closed'], true) ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        MaintenanceRequest::insert($rows);
    }

    /** @return array<int> */
    private function seedCustomFields($now): array
    {
        CustomField::query()->delete();

        $fields = [
            // Shown in the stats row on the listing page alongside bedrooms/bathrooms.
            ['all', 'Kitchen', 'kitchen', 'number', null],
            ['all', 'Balcony', 'balcony', 'number', null],
            ['rent', 'Pet Friendly', 'pet_friendly', 'checkbox', null],
            ['rent', 'Lift Available', 'lift_available', 'checkbox', null],
            ['sale', 'Ownership Type', 'ownership_type', 'select', ['Freehold', 'Leasehold']],
            ['sale', 'Facing Direction', 'facing_direction', 'select', ['North', 'South', 'East', 'West']],
            ['hotel', 'Free Breakfast', 'free_breakfast', 'checkbox', null],
            ['hotel', 'Wifi Speed', 'wifi_speed', 'text', null],
            ['mess', 'Meal Included', 'meal_included', 'checkbox', null],
            ['land', 'Land Type', 'land_type', 'select', ['Commercial', 'Residential', 'Agricultural']],
            ['land', 'Road Width (ft)', 'road_width', 'number', null],
            ['rent', 'Nearby Landmark', 'nearby_landmark', 'text', null],
        ];

        $rows = [];
        foreach ($fields as $i => [$type, $label, $key, $fieldType, $options]) {
            $rows[] = [
                'property_type' => $type,
                'label' => $label,
                'key' => $key,
                'type' => $fieldType,
                'options' => $options ? json_encode($options) : null,
                'is_required' => false,
                'is_filterable' => in_array($fieldType, ['select', 'checkbox'], true),
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        CustomField::insert($rows);

        return CustomField::whereIn('key', array_column($fields, 2))->pluck('id')->all();
    }

    private function seedCustomFieldValues(array $customFieldIds, array $listingIds, $now): void
    {
        if (empty($customFieldIds)) {
            return;
        }

        $targets = array_slice($listingIds, 0, 25);
        CustomFieldValue::whereIn('property_listing_id', $targets)->delete();

        $rows = [];
        foreach ($targets as $i => $listingId) {
            $rows[] = [
                'property_listing_id' => $listingId,
                'custom_field_id' => $customFieldIds[$i % count($customFieldIds)],
                'value' => ['1', 'Freehold', 'North', '10 Mbps', '40'][$i % 5],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        CustomFieldValue::insert($rows);

        $this->seedRoomCountValues($listingIds, $now);
    }

    /** Kitchen/balcony counts are custom fields, so give every demo listing a value for them. */
    private function seedRoomCountValues(array $listingIds, $now): void
    {
        $roomFields = CustomField::whereIn('key', ['kitchen', 'balcony'])->pluck('id', 'key');

        if ($roomFields->isEmpty()) {
            return;
        }

        CustomFieldValue::whereIn('custom_field_id', $roomFields->values())->delete();

        foreach (array_chunk($listingIds, 250) as $chunk) {
            $rows = [];

            foreach ($chunk as $listingId) {
                foreach (['kitchen' => random_int(1, 2), 'balcony' => random_int(0, 4)] as $key => $count) {
                    $rows[] = [
                        'property_listing_id' => $listingId,
                        'custom_field_id' => $roomFields[$key],
                        'value' => (string) $count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            CustomFieldValue::insert($rows);
        }
    }

    /** @return array<int> */
    private function seedCoupons($now): array
    {
        Coupon::query()->delete();

        $rows = [];
        for ($n = 1; $n <= 10; $n++) {
            $type = $n % 2 === 0 ? 'percentage' : 'fixed';
            $rows[] = [
                'code' => 'DEMO'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'type' => $type,
                'value' => $type === 'percentage' ? random_int(5, 30) : random_int(100, 2000),
                'max_discount' => $type === 'percentage' ? 3000 : null,
                'min_order' => random_int(0, 5000),
                'max_uses' => random_int(20, 200),
                'used_count' => random_int(0, 15),
                'max_uses_per_user' => 1,
                'applicable_for' => ['subscription', 'listing_package', 'booking'][$n % 3],
                'starts_at' => $now->copy()->subDays(10),
                'expires_at' => $now->copy()->addDays(60),
                'is_active' => $n % 5 !== 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Coupon::insert($rows);

        return Coupon::orderByDesc('id')->limit(10)->pluck('id')->all();
    }

    /** @return array<int> */
    private function seedSubscriptionPlans($now): array
    {
        // Delete dependents first — user_subscriptions.subscription_plan_id is ON DELETE RESTRICT.
        UserSubscription::whereIn('subscription_plan_id', SubscriptionPlan::pluck('id'))->delete();
        SubscriptionPlan::query()->delete();

        // Feature keys must match GatesRentManagementFeatures + the entitlement service.
        // Higher tiers progressively unlock the rent-management toolset so the dashboard
        // is actually reachable — previously no plan granted any of these.
        $plans = [
            ['Free', 0, 30, 2, []],
            ['Starter', 50000, 30, 10, ['featured_listings', 'rent_reminders']],
            ['Growth', 150000, 30, 30, ['featured_listings', 'rent_reminders', 'receipts', 'income_tracking']],
            ['Business', 300000, 90, 100, ['featured_listings', 'priority_support', 'rent_reminders', 'receipts', 'income_tracking', 'expense_tracking']],
            ['Agency', 600000, 180, 300, ['featured_listings', 'priority_support', 'rent_reminders', 'receipts', 'income_tracking', 'expense_tracking', 'agreement_templates', 'multi_unit']],
            ['Enterprise', 1200000, 365, 1000, ['featured_listings', 'priority_support', 'rent_reminders', 'receipts', 'income_tracking', 'expense_tracking', 'agreement_templates', 'multi_unit']],
        ];

        $allFeatures = ['featured_listings', 'priority_support', 'rent_reminders', 'receipts', 'income_tracking', 'expense_tracking', 'agreement_templates', 'multi_unit'];

        $rows = [];
        foreach ($plans as $i => [$name, $price, $duration, $postLimit, $enabled]) {
            // Store every feature as a key so the plan card can show enabled vs locked.
            $features = [];
            foreach ($allFeatures as $feature) {
                $features[$feature] = in_array($feature, $enabled, true);
            }

            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "The {$name} plan for property owners and agencies.",
                'price' => $price,
                'duration_days' => $duration,
                'post_limit' => $postLimit,
                'unlocked_features' => json_encode($features),
                'is_featured' => $i === 2,
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        SubscriptionPlan::insert($rows);

        return SubscriptionPlan::orderBy('sort_order')->pluck('id')->all();
    }

    /** @return array<int> */
    private function seedUserSubscriptions(array $ownerIds, array $planIds, $now): array
    {
        if (empty($planIds)) {
            return [];
        }

        UserSubscription::whereIn('user_id', $ownerIds)->delete();

        $statuses = ['active', 'expired', 'cancelled', 'pending'];
        $targetOwners = array_slice($ownerIds, 0, 15);
        $rows = [];
        foreach ($targetOwners as $i => $ownerId) {
            $status = $statuses[$i % count($statuses)];
            $start = $now->copy()->subDays(random_int(1, 60));
            $rows[] = [
                'user_id' => $ownerId,
                'subscription_plan_id' => $planIds[$i % count($planIds)],
                'starts_at' => $start,
                'ends_at' => $start->copy()->addDays(30),
                'status' => $status,
                'posts_used' => random_int(0, 10),
                'auto_renew' => $i % 2 === 0,
                'cancelled_at' => $status === 'cancelled' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        UserSubscription::insert($rows);

        return UserSubscription::whereIn('user_id', $targetOwners)->pluck('id')->all();
    }

    private function seedListingPackages($now): void
    {
        ListingPackage::query()->delete();

        $packages = [
            ['Single Post', 2000, 1, 30],
            ['5-Pack', 8000, 5, 30],
            ['10-Pack', 15000, 10, 60],
            ['Featured Boost', 5000, 1, 15],
            ['Agency Bundle', 40000, 50, 90],
            ['Trial Pack', 0, 1, 7],
        ];

        $rows = [];
        foreach ($packages as $i => [$name, $price, $quota, $duration]) {
            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'price' => $price,
                'post_quota' => $quota,
                'duration_days' => $duration,
                'features' => json_encode(['bump_up' => $i === 3]),
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        ListingPackage::insert($rows);
    }

    private function seedPayments(array $userIds, array $userSubscriptionIds, array $couponIds, $now): void
    {
        Payment::query()->delete();

        $technicianBookingIds = TechnicianBooking::pluck('id')->all();
        $hotelBookingIds = HotelBooking::pluck('id')->all();

        $payables = [];
        foreach ($userSubscriptionIds as $id) {
            $payables[] = [UserSubscription::class, $id];
        }
        foreach (array_slice($technicianBookingIds, 0, 8) as $id) {
            $payables[] = [TechnicianBooking::class, $id];
        }
        foreach (array_slice($hotelBookingIds, 0, 8) as $id) {
            $payables[] = [HotelBooking::class, $id];
        }

        if (empty($payables)) {
            return;
        }

        $statuses = ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'];
        $gateways = ['stripe', 'sslcommerz', 'bkash', 'nagad'];

        $rows = [];
        foreach ($payables as $i => [$type, $id]) {
            $status = $statuses[$i % count($statuses)];
            $amount = random_int(500, 50000);
            $useCoupon = $i % 4 === 0 && ! empty($couponIds);

            $rows[] = [
                'user_id' => $userIds[$i % count($userIds)],
                'payable_type' => $type,
                'payable_id' => $id,
                'gateway' => $gateways[$i % count($gateways)],
                'amount' => $amount,
                'currency' => 'USD',
                'gateway_fee' => (int) round($amount * 0.02),
                'status' => $status,
                'transaction_ref' => 'TXN-'.Str::upper(Str::random(10)),
                'gateway_ref' => 'GW-'.Str::upper(Str::random(8)),
                'gateway_response' => json_encode(['status' => $status, 'raw' => 'demo']),
                'coupon_id' => $useCoupon ? $couponIds[$i % count($couponIds)] : null,
                'discount_amount' => $useCoupon ? random_int(100, 1000) : 0,
                'paid_at' => $status === 'paid' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Payment::insert($rows);
    }

    private function seedWalletTransactions(array $userIds): void
    {
        $walletService = app(WalletService::class);

        foreach (array_slice($userIds, 0, 10) as $i => $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $wallet = $walletService->findOrCreateForUser($user);

            $walletService->credit($wallet, random_int(1000, 10000), 'Demo wallet top-up');
            if ($i % 2 === 0) {
                $walletService->debit($wallet, random_int(200, 900), 'Demo service payment');
            }
        }

        if (Wallet::count() < 5) {
            $this->command->warn('Fewer than 5 wallets exist after seeding wallet transactions.');
        }
    }

    private function seedBlocks(array $userIds, $now): void
    {
        Block::query()->delete();

        // A single demo block between the last two users, just to exercise the feature.
        // (A chain across every user walls off messaging between the main test accounts.)
        $count = \count($userIds);

        if ($count < 2) {
            return;
        }

        Block::insert([[
            'blocker_id' => $userIds[$count - 1],
            'blocked_id' => $userIds[$count - 2],
            'created_at' => $now,
            'updated_at' => $now,
        ]]);
    }

    private function seedReports(array $userIds, array $listingIds, array $technicianIds, $now): void
    {
        Report::query()->delete();

        $reasons = ['spam', 'fraud', 'inappropriate_content', 'duplicate', 'wrong_information'];
        $statuses = ['pending', 'reviewed', 'actioned', 'dismissed'];

        $reportables = [];
        foreach (array_slice($listingIds, 0, 10) as $id) {
            $reportables[] = [PropertyListing::class, $id];
        }
        foreach (array_slice($technicianIds, 0, 5) as $id) {
            $reportables[] = [Technician::class, $id];
        }

        $rows = [];
        foreach ($reportables as $i => [$type, $id]) {
            $rows[] = [
                'reporter_id' => $userIds[$i % count($userIds)],
                'reportable_type' => $type,
                'reportable_id' => $id,
                'reason' => $reasons[$i % count($reasons)],
                'details' => fake()->sentence(12),
                'status' => $statuses[$i % count($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Report::insert($rows);
    }
}
