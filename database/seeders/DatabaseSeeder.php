<?php

namespace Database\Seeders;

use App\Services\NotificationCenter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeding creates thousands of demo records; suppress the activity notifications
        // those creates would otherwise fan out to every admin.
        NotificationCenter::silenced(fn () => $this->call([
            CountrySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            LanguageSeeder::class,
            CurrencySeeder::class,
            ZoneSeeder::class,
            EmailTemplateSeeder::class,
            DummyDataSeeder::class,
            TechnicianDemoSeeder::class,
            MarketplaceDemoSeeder::class,
            AgencyLogoSeeder::class,
            AgentAvatarSeeder::class,
            TestimonialSeeder::class,
            ProjectCategorySeeder::class,
            ProjectSeeder::class,
            CmsDemoSeeder::class,
            FaqSeeder::class,
            // Runs last: replaces placeholder imagery with curated property photos and
            // seeds a full rent-management lifecycle on the demo landlord's listings.
            ShowcaseSeeder::class,
            RentManagementSeeder::class,
            // A minimal global demo: famous-city listings, each in its local currency.
            GlobalShowcaseSeeder::class,
        ]));
    }
}
