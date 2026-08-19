<?php

namespace Database\Seeders;

use App\Models\AreaLandingPage;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\CmsPage;
use App\Models\Language;
use App\Models\ReferralRule;
use App\Models\Translation;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds banners, blogs/blog categories, CMS pages, referral rules, area
 * landing pages and translations — content-side demo data linked to real
 * admin/owner users, zones and languages.
 *
 * Run: php artisan db:seed --class=CmsDemoSeeder
 */
class CmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $authorId = User::where('email', 'admin@rentdo.com')->value('id')
            ?? User::query()->value('id');

        $categoryIds = $this->seedBlogCategories($now);
        $this->seedBlogs($authorId, $categoryIds, $now);
        $this->seedCmsPages($authorId, $now);
        $this->seedBanners($now);
        $this->seedReferralRules($now);
        $this->seedAreaLandingPages($now);
        $this->seedTranslations($now);

        $this->command->info('CMS demo data seeded.');
    }

    /** @return array<int> */
    private function seedBlogCategories($now): array
    {
        BlogCategory::query()->delete();

        $names = ['Buying Guide', 'Renting Tips', 'Market Trends', 'Home Improvement', 'Neighborhood Spotlight', 'Legal Advice'];
        $rows = [];
        foreach ($names as $i => $name) {
            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Articles about {$name}.",
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        BlogCategory::insert($rows);

        return BlogCategory::pluck('id')->all();
    }

    private function seedBlogs(?int $authorId, array $categoryIds, $now): void
    {
        if (! $authorId || empty($categoryIds)) {
            return;
        }

        Blog::withTrashed()->forceDelete();

        $titles = [
            'Top 10 Tips for First-Time Home Buyers',
            'How to Negotiate Rent in a Competitive Market',
            '2026 Property Market Trends to Watch',
            'Simple Upgrades That Boost Your Home Value',
            'Best Neighborhoods for Young Families',
            'Understanding Your Lease Agreement',
            'Bachelor vs Family Rentals: What to Know',
            'How to Spot a Rental Scam',
            'Preparing Your Property for a Quick Sale',
            'The Rise of Short-Stay Hotel Listings',
            'Land Investment Basics for Beginners',
            'Moving Checklist: What to Do Before Move-In Day',
            'Mess Living 101: A Guide for Students',
            'How Agencies Can Grow Their Listings',
            'Seasonal Maintenance Tips for Homeowners',
        ];

        $rows = [];
        foreach ($titles as $i => $title) {
            $status = $i % 5 === 0 ? 'draft' : 'published';
            $rows[] = [
                'author_id' => $authorId,
                'blog_category_id' => $categoryIds[$i % count($categoryIds)],
                'title' => $title,
                'slug' => Str::slug($title),
                'content' => "<p>{$title}. ".fake()->paragraphs(4, true).'</p>',
                'excerpt' => fake()->sentence(20),
                'featured_image' => 'https://picsum.photos/seed/blog'.$i.'/800/450',
                'tags' => json_encode(['property', 'guide']),
                'meta_title' => $title,
                'meta_description' => fake()->sentence(15),
                'status' => $status,
                'view_count' => random_int(10, 5000),
                'published_at' => $status === 'published' ? $now->copy()->subDays(random_int(1, 90)) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Blog::insert($rows);
    }

    private function seedCmsPages(?int $authorId, $now): void
    {
        if (! $authorId) {
            return;
        }

        CmsPage::withTrashed()->forceDelete();

        $pages = [
            'About Us', 'Terms & Conditions', 'Privacy Policy', 'Contact Us',
            'Frequently Asked Questions', 'Refund Policy', 'Careers', 'How It Works',
        ];

        // The nav, footer and public routes all link to /pages/terms-of-service, so the
        // slug stays put even though the page is titled "Terms & Conditions".
        $slugOverrides = ['Terms & Conditions' => 'terms-of-service'];

        $authoredContent = $this->legalPageContent();
        $authoredSummaries = [
            'privacy-policy' => 'We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.',
            'terms-of-service' => 'Please read these Terms and Conditions carefully before using our platform. By accessing or using our services, you agree to be bound by these terms.',
        ];

        $rows = [];
        foreach ($pages as $title) {
            $slug = $slugOverrides[$title] ?? Str::slug($title);

            $rows[] = [
                'created_by' => $authorId,
                'title' => $title,
                'slug' => $slug,
                'content' => $authoredContent[$slug] ?? "<h1>{$title}</h1><p>".fake()->paragraphs(3, true).'</p>',
                'meta_title' => $title,
                'meta_description' => $authoredSummaries[$slug] ?? fake()->sentence(15),
                'featured_image' => null,
                'status' => 'published',
                'published_at' => $now->copy()->subDays(random_int(5, 200)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        CmsPage::insert($rows);
    }

    /**
     * Section-structured copy for the legal pages. The public page builds its
     * table of contents from these `<h2>` headings, so they must stay as H2s.
     *
     * @return array<string, string>
     */
    private function legalPageContent(): array
    {
        $privacySections = [
            ['Information We Collect', 'We collect information that you provide directly to us and information that is collected automatically when you use our services. This includes your name, contact details, the listings you post, and the messages you exchange with other users.'],
            ['How We Use Your Information', 'We use the information we collect to provide, maintain, and improve our services, to communicate with you, and to ensure the security of our platform.'],
            ['How We Share Your Information', 'We do not sell your personal information. We may share your information with trusted third parties who assist us in operating our platform and servicing you, and only to the extent required to deliver those services.'],
            ['Cookies and Tracking Technologies', 'We use cookies and similar technologies to enhance your experience, analyze site traffic, and personalize content. You can control cookies through your browser settings.'],
            ['Data Security', 'We implement industry-standard security measures to protect your information from unauthorized access, alteration, disclosure, or destruction. No method of transmission over the internet is completely secure, so we cannot guarantee absolute security.'],
            ['Your Rights and Choices', 'You have the right to access, update, or delete your personal information. You can also opt out of marketing communications at any time from your account settings.'],
            ['Third-Party Links', 'Our platform may contain links to third-party websites. We are not responsible for the privacy practices or content of those sites.'],
            ["Children's Privacy", 'Our services are not intended for children under 13. We do not knowingly collect personal information from children.'],
            ['Changes to This Policy', 'We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page.'],
            ['Contact Us', 'If you have any questions or concerns about this Privacy Policy, please contact us using the details on our contact page.'],
        ];

        $termsSections = [
            ['Acceptance of Terms', 'By accessing or using our platform, you agree to be bound by these Terms and Conditions and our Privacy Policy. If you do not agree, please do not use our services.'],
            ['Description of Services', 'We provide a platform that connects property buyers, sellers, agents, and agencies. We reserve the right to modify or discontinue any part of our services at any time.'],
            ['User Accounts', 'You may need to create an account to access certain features. You are responsible for maintaining the confidentiality of your account and password.'],
            ['User Responsibilities', 'You agree to provide accurate information and use our platform only for lawful purposes. You are responsible for all activities under your account.'],
            ['Payments and Fees', 'Certain services may require payment. All fees are non-refundable unless stated otherwise. We reserve the right to change pricing with prior notice.'],
            ['Intellectual Property', 'All content, logos, and materials on our platform are our property or licensed to us. You may not copy, reproduce, or distribute any content without permission.'],
            ['Prohibited Activities', 'You agree not to use our platform for any illegal, harmful, or unauthorized activities, including fraud, spam, or data scraping.'],
            ['Disclaimer of Warranties', 'Our services are provided "as is" and "as available". We do not guarantee that our platform will be error-free or uninterrupted.'],
            ['Limitation of Liability', 'We are not liable for any indirect, incidental, or consequential damages arising from your use of our platform.'],
            ['Termination', 'We may suspend or terminate your access to our platform at any time for violation of these terms or for any other reason.'],
            ['Governing Law', 'These Terms shall be governed by the laws of the jurisdiction in which our company is registered, without regard to its conflict of law principles.'],
            ['Changes to Terms', 'We may update these Terms and Conditions from time to time. We will notify you of any changes by posting the updated terms on this page.'],
            ['Contact Us', 'If you have any questions about these Terms and Conditions, please contact us using the details on our contact page.'],
        ];

        return [
            'privacy-policy' => $this->sectionsToHtml($privacySections),
            'terms-of-service' => $this->sectionsToHtml($termsSections),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $sections
     */
    private function sectionsToHtml(array $sections): string
    {
        $html = '';

        foreach ($sections as $index => [$heading, $body]) {
            $number = $index + 1;
            $html .= '<h2>'.$number.'. '.e($heading).'</h2><p>'.e($body).'</p>';
        }

        return $html;
    }

    private function seedBanners($now): void
    {
        Banner::query()->delete();

        $positions = ['home_top', 'home_middle', 'sidebar', 'search_results'];
        $rows = [];
        for ($n = 1; $n <= 10; $n++) {
            $rows[] = [
                'name' => 'Banner #'.$n,
                'image' => 'https://picsum.photos/seed/banner'.$n.'/1200/300',
                'link' => '/listings?promo='.$n,
                'position' => $positions[$n % count($positions)],
                'sort_order' => $n,
                'is_active' => $n % 4 !== 0,
                'starts_at' => $now->copy()->subDays(5),
                'ends_at' => $now->copy()->addDays(30),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Banner::insert($rows);
    }

    private function seedReferralRules($now): void
    {
        ReferralRule::query()->delete();

        $rules = [
            ['Standard Referral', 'fixed', 5000, 'Earn a fixed bonus for every referred sign-up.'],
            ['Agency Referral', 'percentage', 10, 'Agencies earn a commission percentage.'],
            ['Launch Promo', 'fixed', 10000, 'Limited-time higher referral bonus.'],
            ['Technician Referral', 'fixed', 3000, 'Referral bonus for bringing in new technicians.'],
            ['Renewal Referral', 'percentage', 5, 'Bonus on subscription renewals from referrals.'],
        ];

        $rows = [];
        foreach ($rules as $i => [$name, $type, $value, $description]) {
            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'reward_type' => $type,
                'reward_value' => $value,
                'reward_description' => $description,
                'max_referrals' => 100,
                'min_purchase_amount' => 1000,
                'is_active' => $i !== 4,
                'starts_at' => $now->copy()->subDays(30),
                'expires_at' => $now->copy()->addDays(180),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        ReferralRule::insert($rows);
    }

    private function seedAreaLandingPages($now): void
    {
        AreaLandingPage::query()->delete();

        $zoneIds = Zone::where('type', 'area')->limit(15)->pluck('id', 'name');

        $rows = [];
        foreach ($zoneIds as $name => $zoneId) {
            $rows[] = [
                'zone_id' => $zoneId,
                'headline' => "Find Your Next Home in {$name}",
                'subheadline' => "Browse verified rentals, sales and hotel stays in {$name}.",
                'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
                'featured_image' => 'https://picsum.photos/seed/area'.$zoneId.'/1000/500',
                'features' => json_encode(['verified_listings' => true, 'local_agents' => true]),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        AreaLandingPage::insert($rows);
    }

    private function seedTranslations($now): void
    {
        Translation::query()->delete();

        $languageIds = Language::pluck('id')->all();
        if (empty($languageIds)) {
            return;
        }

        $entries = [
            'welcome_message' => 'Welcome to Rentdo',
            'search_placeholder' => 'Search for properties, technicians...',
            'listing_type_rent' => 'Rent',
            'listing_type_sale' => 'Sale',
            'listing_type_hotel' => 'Hotel',
            'listing_type_mess' => 'Mess',
            'listing_type_land' => 'Land',
            'book_now' => 'Book Now',
            'contact_owner' => 'Contact Owner',
            'view_details' => 'View Details',
        ];

        $rows = [];
        foreach ($languageIds as $languageId) {
            foreach ($entries as $key => $value) {
                $rows[] = [
                    'language_id' => $languageId,
                    'group' => 'frontend',
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        Translation::insert($rows);
    }
}
