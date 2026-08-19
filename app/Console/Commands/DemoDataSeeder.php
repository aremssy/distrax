<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\CmsPage;
use App\Models\Coupon;
use App\Models\PropertyListing;
use App\Models\SubscriptionPlan;
use App\Models\Technician;
use App\Models\TechnicianCategory;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('demo:seed {--revert : Remove all demo data}')]
#[Description('Seed demo data for testing and preview purposes. Reversible with --revert.')]
class DemoDataSeeder extends Command
{
    private array $createdIds = [
        'users' => [],
        'zones' => [],
        'listings' => [],
        'technicians' => [],
        'blog_posts' => [],
        'cms_pages' => [],
        'plans' => [],
        'coupons' => [],
    ];

    public function handle(): int
    {
        if ($this->option('revert')) {
            return $this->revert();
        }

        $this->components->task('Creating demo zones', fn () => $this->createZones());
        $this->components->task('Creating demo users', fn () => $this->createUsers());
        $this->components->task('Creating demo listings', fn () => $this->createListings());
        $this->components->task('Creating demo technicians', fn () => $this->createTechnicians());
        $this->components->task('Creating subscription plans', fn () => $this->createPlans());
        $this->components->task('Creating coupons', fn () => $this->createCoupons());
        $this->components->task('Creating blog content', fn () => $this->createBlogContent());
        $this->components->task('Creating CMS pages', fn () => $this->createCmsPages());

        $this->storeMetadata();

        $this->components->info('Demo data seeded successfully!');

        return self::SUCCESS;
    }

    private function createZones(): void
    {
        $country = Zone::create(['name' => 'United States', 'slug' => 'united-states', 'type' => 'country', 'is_active' => true, 'sort_order' => 1]);
        $this->createdIds['zones'][] = $country->id;

        $states = ['California', 'New York', 'Texas', 'Florida'];
        foreach ($states as $i => $state) {
            $s = Zone::create(['parent_id' => $country->id, 'name' => $state, 'slug' => Str::slug($state), 'type' => 'state', 'is_active' => true, 'sort_order' => $i + 1]);
            $this->createdIds['zones'][] = $s->id;

            $cities = match ($state) {
                'California' => ['Los Angeles', 'San Francisco', 'San Diego'],
                'New York' => ['New York City', 'Buffalo', 'Rochester'],
                'Texas' => ['Houston', 'Austin', 'Dallas'],
                'Florida' => ['Miami', 'Orlando', 'Tampa'],
                default => [],
            };

            foreach ($cities as $j => $city) {
                $c = Zone::create(['parent_id' => $s->id, 'name' => $city, 'slug' => Str::slug($city), 'type' => 'area', 'is_active' => true, 'sort_order' => $j + 1]);
                $this->createdIds['zones'][] = $c->id;
            }
        }
    }

    private function createUsers(): void
    {
        $demoUsers = [
            ['name' => 'John Doe', 'email' => 'demo@example.com', 'role' => 'owner'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'owner'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'renter'],
            ['name' => 'Alice Brown', 'email' => 'alice@example.com', 'role' => 'renter'],
        ];

        foreach ($demoUsers as $u) {
            $user = User::create([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password'),
                'verification_status' => 'verified',
                'phone' => fake()->phoneNumber(),
            ]);
            $this->createdIds['users'][] = $user->id;
        }
    }

    private function createListings(): void
    {
        $owners = User::whereIn('email', ['demo@example.com', 'jane@example.com'])->get();
        $zones = Zone::where('type', 'area')->get();

        $listings = [
            ['title' => 'Modern Downtown Apartment', 'type' => 'rent', 'price' => 2500, 'bedrooms' => 2, 'bathrooms' => 1, 'description' => 'Beautiful modern apartment in the heart of downtown with stunning city views.'],
            ['title' => 'Luxury Villa with Pool', 'type' => 'rent', 'price' => 5000, 'bedrooms' => 4, 'bathrooms' => 3, 'description' => 'Spacious luxury villa with private pool and garden. Perfect for families.'],
            ['title' => 'Cozy Studio Near University', 'type' => 'rent', 'price' => 1200, 'bedrooms' => 1, 'bathrooms' => 1, 'description' => 'Affordable studio apartment within walking distance to the university campus.'],
            ['title' => 'Beachfront Condo', 'type' => 'sale', 'price' => 350000, 'bedrooms' => 3, 'bathrooms' => 2, 'description' => 'Stunning beachfront condo with panoramic ocean views and direct beach access.'],
            ['title' => 'Commercial Office Space', 'type' => 'rent', 'price' => 4000, 'bedrooms' => 0, 'bathrooms' => 2, 'description' => 'Prime commercial office space in the business district with modern amenities.'],
            ['title' => 'Charming Townhouse', 'type' => 'sale', 'price' => 280000, 'bedrooms' => 3, 'bathrooms' => 2, 'description' => 'Charming townhouse in a quiet neighborhood with a private backyard.'],
            ['title' => 'Mountain View Cabin', 'type' => 'rent', 'price' => 1800, 'bedrooms' => 2, 'bathrooms' => 1, 'description' => 'Cozy mountain cabin with breathtaking views. Perfect weekend getaway.'],
            ['title' => 'Penthouse Suite', 'type' => 'sale', 'price' => 1500000, 'bedrooms' => 5, 'bathrooms' => 4, 'description' => 'Ultimate luxury penthouse with panoramic views, private terrace, and premium finishes.'],
        ];

        foreach ($listings as $i => $data) {
            $listing = PropertyListing::create([
                'zone_id' => $zones->random()->id,
                'owner_id' => $owners->random()->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'description' => $data['description'],
                'price' => $data['price'],
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'area_sqft' => rand(500, 3000),
                'status' => 'active',
                'is_featured' => $i < 3,
                'address' => fake()->address(),
                'published_at' => now()->subDays(rand(1, 60)),
            ]);
            $this->createdIds['listings'][] = $listing->id;
        }
    }

    private function createTechnicians(): void
    {
        $categories = ['Plumber', 'Electrician', 'Carpenter', 'Painter', 'Gardener', 'Cleaner'];
        $createdCategories = [];

        foreach ($categories as $cat) {
            $category = TechnicianCategory::create([
                'name' => $cat,
                'slug' => Str::slug($cat),
                'is_active' => true,
                'sort_order' => count($createdCategories) + 1,
            ]);
            $createdCategories[] = $category;
        }

        $zones = Zone::where('type', 'area')->get();
        $techUsers = [
            ['name' => 'Mike Plumber', 'email' => 'mike@example.com'],
            ['name' => 'Sarah Electric', 'email' => 'sarah@example.com'],
            ['name' => 'Tom Carpenter', 'email' => 'tom@example.com'],
        ];

        foreach ($techUsers as $i => $t) {
            $user = User::create([
                'name' => $t['name'],
                'email' => $t['email'],
                'password' => Hash::make('password'),
                'verification_status' => 'verified',
                'phone' => fake()->phoneNumber(),
            ]);
            $this->createdIds['users'][] = $user->id;

            $tech = Technician::create([
                'user_id' => $user->id,
                'technician_category_id' => $createdCategories[$i]->id,
                'zone_id' => $zones->random()->id,
                'bio' => 'Experienced professional with years of expertise.',
                'skills' => [Str::slug($categories[$i])],
                'experience_years' => rand(3, 15),
                'hourly_rate' => rand(30, 100),
                'is_available' => true,
                'is_verified' => true,
                'status' => 'active',
            ]);
            $this->createdIds['technicians'][] = $tech->id;
        }
    }

    private function createPlans(): void
    {
        $plans = [
            ['name' => 'Basic', 'price' => 0, 'duration_days' => 30, 'post_limit' => 3, 'description' => 'Perfect for getting started with a few listings.'],
            ['name' => 'Standard', 'price' => 29.99, 'duration_days' => 30, 'post_limit' => 15, 'description' => 'For growing businesses with moderate listing needs.', 'is_featured' => true],
            ['name' => 'Premium', 'price' => 79.99, 'duration_days' => 30, 'post_limit' => 50, 'description' => 'Unlimited listings and premium features for serious professionals.', 'is_featured' => true],
            ['name' => 'Enterprise', 'price' => 199.99, 'duration_days' => 90, 'post_limit' => 200, 'description' => 'Complete solution for large agencies and enterprises.'],
        ];

        foreach ($plans as $p) {
            $plan = SubscriptionPlan::create([
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'description' => $p['description'],
                'price' => $p['price'],
                'duration_days' => $p['duration_days'],
                'post_limit' => $p['post_limit'],
                'is_active' => true,
                'is_featured' => $p['is_featured'] ?? false,
                'sort_order' => count($this->createdIds['plans']) + 1,
            ]);
            $this->createdIds['plans'][] = $plan->id;
        }
    }

    private function createCoupons(): void
    {
        $coupons = [
            ['code' => 'WELCOME20', 'type' => 'percentage', 'value' => 20, 'max_uses' => 100, 'description' => '20% off for new users'],
            ['code' => 'SAVE50', 'type' => 'fixed', 'value' => 50, 'max_uses' => 50, 'description' => '$50 off any listing'],
            ['code' => 'SPRING25', 'type' => 'percentage', 'value' => 25, 'max_uses' => 200, 'description' => 'Spring special - 25% off'],
        ];

        foreach ($coupons as $c) {
            $coupon = Coupon::create([
                'code' => $c['code'],
                'type' => $c['type'],
                'value' => $c['value'],
                'max_uses' => $c['max_uses'],
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ]);
            $this->createdIds['coupons'][] = $coupon->id;
        }
    }

    private function createBlogContent(): void
    {
        $categories = ['Market Insights', 'Renting Tips', 'Home Improvement', 'Local Guides', 'Investment Advice'];
        $createdCategories = [];

        foreach ($categories as $i => $cat) {
            $c = BlogCategory::create([
                'name' => $cat,
                'slug' => Str::slug($cat),
                'description' => "Articles about {$cat}",
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
            $createdCategories[] = $c;
        }

        $admin = User::where('email', 'demo@example.com')->first() ?? User::first();

        $posts = [
            ['title' => 'Top 10 Tips for First-Time Renters', 'category' => 'Renting Tips'],
            ['title' => 'Understanding the Real Estate Market in 2026', 'category' => 'Market Insights'],
            ['title' => 'How to Increase Your Property Value', 'category' => 'Home Improvement'],
            ['title' => 'Best Neighborhoods for Families', 'category' => 'Local Guides'],
            ['title' => 'Is Real Estate a Good Investment?', 'category' => 'Investment Advice'],
            ['title' => 'A Complete Guide to Property Management', 'category' => 'Renting Tips'],
        ];

        foreach ($posts as $i => $p) {
            $blog = Blog::create([
                'author_id' => $admin->id,
                'blog_category_id' => $createdCategories[array_search($p['category'], $categories)]->id,
                'title' => $p['title'],
                'slug' => Str::slug($p['title']),
                'content' => str_repeat('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>', rand(3, 8)),
                'excerpt' => 'A comprehensive guide on '.$p['title'],
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 90)),
                'tags' => [Str::slug($p['category']), 'real-estate'],
            ]);
            $this->createdIds['blog_posts'][] = $blog->id;
        }
    }

    private function createCmsPages(): void
    {
        $admin = User::where('email', 'demo@example.com')->first() ?? User::first();

        $pages = [
            ['title' => 'About Us', 'content' => '<h2>Our Story</h2><p>We are the leading property marketplace connecting buyers, sellers, and renters.</p>'],
            ['title' => 'Terms of Service', 'content' => '<h2>Terms</h2><p>Please read these terms carefully before using our platform.</p>'],
            ['title' => 'Privacy Policy', 'content' => '<h2>Privacy</h2><p>We respect your privacy and are committed to protecting your personal data.</p>'],
            ['title' => 'FAQ', 'content' => '<h2>Frequently Asked Questions</h2><p>Find answers to common questions about our platform.</p>'],
        ];

        foreach ($pages as $p) {
            $page = CmsPage::create([
                'created_by' => $admin->id,
                'title' => $p['title'],
                'slug' => Str::slug($p['title']),
                'content' => $p['content'],
                'status' => 'published',
                'published_at' => now(),
            ]);
            $this->createdIds['cms_pages'][] = $page->id;
        }
    }

    private function storeMetadata(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'demo_data_seeded_at'],
            ['value' => now()->toISOString(), 'group' => 'system', 'type' => 'string']
        );

        foreach ($this->createdIds as $type => $ids) {
            DB::table('settings')->updateOrInsert(
                ['key' => "demo_data_{$type}"],
                ['value' => json_encode($ids), 'group' => 'system', 'type' => 'json']
            );
        }
    }

    private function revert(): int
    {
        $types = ['coupons', 'plans', 'cms_pages', 'blog_posts', 'technicians', 'listings', 'zones', 'users'];

        foreach ($types as $type) {
            $meta = DB::table('settings')->where('key', "demo_data_{$type}")->value('value');
            if (! $meta) {
                continue;
            }

            $ids = json_decode($meta, true);
            if (! is_array($ids)) {
                continue;
            }

            $modelClass = match ($type) {
                'users' => User::class,
                'zones' => Zone::class,
                'listings' => PropertyListing::class,
                'technicians' => Technician::class,
                'blog_posts' => Blog::class,
                'cms_pages' => CmsPage::class,
                'plans' => SubscriptionPlan::class,
                'coupons' => Coupon::class,
            };

            $modelClass::whereIn('id', $ids)->forceDelete();
            DB::table('settings')->where('key', "demo_data_{$type}")->delete();
        }

        DB::table('blog_categories')->truncate();
        DB::table('technician_categories')->truncate();

        DB::table('settings')->where('key', 'demo_data_seeded_at')->delete();

        $this->components->info('All demo data removed successfully.');

        return self::SUCCESS;
    }
}
