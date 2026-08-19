<?php

namespace App\Support;

/**
 * Registry of editable homepage/footer content strings.
 *
 * Defines how the admin "Homepage" editor is grouped and labelled. Each field's
 * `text` is both the English default shown on the site and the translation key
 * itself — the same flat-string convention as every other `__()` call in the
 * app, resolved through `lang/en.json` / `lang/bn.json` and, for admin
 * overrides, the `translations` table (group "*") via DatabaseTranslationLoader.
 */
class HomeContent
{
    /**
     * Editable fields grouped by homepage section.
     *
     * @return array<string, array<string, array{label: string, text: string, type?: string}>>
     */
    public static function groups(): array
    {
        return [
            'Meta' => [
                'home_meta_title' => ['label' => 'Page title', 'text' => 'Rentdo - Your Dream Property Awaits'],
            ],
            'Hero Slider' => [
                'hero_1_title' => ['label' => 'Slide 1 — Title', 'text' => 'Find the right property.'],
                'hero_1_highlight' => ['label' => 'Slide 1 — Highlight', 'text' => 'Right place. Right price.'],
                'hero_1_subtitle' => ['label' => 'Slide 1 — Subtitle', 'type' => 'textarea', 'text' => 'Discover verified properties for rent, sale or investment. Right choices, trusted by thousands.'],
                'hero_2_title' => ['label' => 'Slide 2 — Title', 'text' => 'Verified listings from'],
                'hero_2_highlight' => ['label' => 'Slide 2 — Highlight', 'text' => 'Trusted agents only.'],
                'hero_2_subtitle' => ['label' => 'Slide 2 — Subtitle', 'type' => 'textarea', 'text' => 'Every property is checked by our team so you can browse and book with confidence.'],
                'hero_3_title' => ['label' => 'Slide 3 — Title', 'text' => 'List your property in'],
                'hero_3_highlight' => ['label' => 'Slide 3 — Highlight', 'text' => 'Just a few minutes.'],
                'hero_3_subtitle' => ['label' => 'Slide 3 — Subtitle', 'type' => 'textarea', 'text' => 'Reach thousands of active buyers and tenants by posting your property for free.'],
                'hero_cta' => ['label' => 'Call-to-action button', 'text' => 'Explore Properties'],
                'hero_search_location' => ['label' => 'Search — Location label', 'text' => 'Location'],
                'hero_search_area' => ['label' => 'Search — Area label', 'text' => 'Area'],
                'hero_search_price' => ['label' => 'Search — Price label', 'text' => 'Price Range'],
                'hero_search_button' => ['label' => 'Search — Button', 'text' => 'Search Properties'],
            ],
            'Statistics' => [
                'stat_listings_title' => ['label' => 'Listings — Title', 'text' => 'Properties Listed'],
                'stat_listings_desc' => ['label' => 'Listings — Description', 'text' => 'Active properties across popular locations.'],
                'stat_clients_title' => ['label' => 'Clients — Title', 'text' => 'Happy Clients'],
                'stat_clients_desc' => ['label' => 'Clients — Description', 'text' => 'Satisfied buyers and tenants found their perfect space.'],
                'stat_agents_title' => ['label' => 'Agents — Title', 'text' => 'Expert Agents'],
                'stat_agents_desc' => ['label' => 'Agents — Description', 'text' => 'Professional agents ready to help you.'],
                'stat_rating_title' => ['label' => 'Rating — Title', 'text' => 'User Rating'],
                'stat_rating_desc' => ['label' => 'Rating — Description', 'text' => 'Rated by clients from visible marketplace reviews.'],
            ],
            'Featured Categories' => [
                'categories_eyebrow' => ['label' => 'Eyebrow', 'text' => 'Browse by type'],
                'categories_title' => ['label' => 'Title', 'text' => 'Featured Categories'],
                'categories_cta' => ['label' => 'Link label', 'text' => 'Explore All Categories'],
                'category_sale_label' => ['label' => 'For Sale — Label', 'text' => 'For Sale'],
                'category_rent_label' => ['label' => 'For Rent — Label', 'text' => 'For Rent'],
                'category_land_label' => ['label' => 'Land & Commercial — Label', 'text' => 'Land & Commercial'],
                'category_hotel_label' => ['label' => 'Short Stay — Label', 'text' => 'Short Stay'],
                'category_office_label' => ['label' => 'Office Space — Label', 'text' => 'Office Space'],
                'category_mess_label' => ['label' => 'Mess / PG — Label', 'text' => 'Mess / PG'],
            ],
            'Featured Properties' => [
                'featured_title' => ['label' => 'Title', 'text' => 'Featured Properties'],
                'featured_cta' => ['label' => 'Link label', 'text' => 'View all properties'],
                'featured_empty_title' => ['label' => 'Empty state — Title', 'text' => 'New properties are coming soon'],
                'featured_empty_text' => ['label' => 'Empty state — Text', 'type' => 'textarea', 'text' => 'Browse all available properties or check back shortly.'],
            ],
            'Why Choose Us' => [
                'why_title' => ['label' => 'Title', 'text' => 'Why Choose Rentdo?'],
                'why_benefit_1_title' => ['label' => 'Benefit 1 — Title', 'text' => 'Verified Listings'],
                'why_benefit_1_desc' => ['label' => 'Benefit 1 — Description', 'text' => 'All listings are verified for your safety.'],
                'why_benefit_2_title' => ['label' => 'Benefit 2 — Title', 'text' => 'Trusted Agents'],
                'why_benefit_2_desc' => ['label' => 'Benefit 2 — Description', 'text' => 'Work with professional and trusted agents.'],
                'why_benefit_3_title' => ['label' => 'Benefit 3 — Title', 'text' => 'Secure Transactions'],
                'why_benefit_3_desc' => ['label' => 'Benefit 3 — Description', 'text' => 'We ensure secure and transparent transactions.'],
                'why_benefit_4_title' => ['label' => 'Benefit 4 — Title', 'text' => 'Easy Property Search'],
                'why_benefit_4_desc' => ['label' => 'Benefit 4 — Description', 'text' => 'Advanced filters to find the perfect property.'],
                'why_benefit_5_title' => ['label' => 'Benefit 5 — Title', 'text' => 'Schedule Visits'],
                'why_benefit_5_desc' => ['label' => 'Benefit 5 — Description', 'text' => 'Book property visits online with ease.'],
                'why_benefit_6_title' => ['label' => 'Benefit 6 — Title', 'text' => 'Mobile Access'],
                'why_benefit_6_desc' => ['label' => 'Benefit 6 — Description', 'text' => 'Access everything on the go.'],
            ],
            'Property by Location' => [
                'location_title' => ['label' => 'Title', 'text' => 'Property by Location'],
                'location_subtitle' => ['label' => 'Subtitle', 'text' => 'Explore active properties in popular areas.'],
                'location_cta' => ['label' => 'Link label', 'text' => 'View all locations'],
                'location_empty' => ['label' => 'Empty state', 'type' => 'textarea', 'text' => 'Locations will appear here as soon as active property listings are available.'],
            ],
            'New Projects' => [
                'projects_eyebrow' => ['label' => 'Eyebrow', 'text' => 'New development'],
                'projects_title' => ['label' => 'Title', 'text' => 'New Projects'],
                'projects_cta' => ['label' => 'Link label', 'text' => 'View all projects'],
            ],
            'Agents & Agencies' => [
                'agents_title' => ['label' => 'Agents — Title', 'text' => 'Top Rated Agents'],
                'agents_cta' => ['label' => 'Agents — Link label', 'text' => 'View All Agents'],
                'agents_empty' => ['label' => 'Agents — Empty state', 'text' => 'No agents yet.'],
                'agencies_title' => ['label' => 'Agencies — Title', 'text' => 'Top Agencies'],
                'agencies_cta' => ['label' => 'Agencies — Link label', 'text' => 'View All Agencies'],
                'agencies_empty' => ['label' => 'Agencies — Empty state', 'text' => 'No agencies yet.'],
            ],
            'App Download Banner' => [
                'app_eyebrow' => ['label' => 'Eyebrow', 'text' => 'Rentdo mobile app'],
                'app_title' => ['label' => 'Title', 'text' => 'Find your next property anywhere.'],
                'app_subtitle' => ['label' => 'Subtitle', 'type' => 'textarea', 'text' => 'Search listings, save favorites, and connect with trusted agents wherever your day takes you.'],
                'app_feature_1' => ['label' => 'Feature pill 1', 'text' => 'Smart property search'],
                'app_feature_2' => ['label' => 'Feature pill 2', 'text' => 'Save your favorites'],
                'app_feature_3' => ['label' => 'Feature pill 3', 'text' => 'Connect with agents'],
            ],
            'Testimonials' => [
                'testimonials_title' => ['label' => 'Title', 'text' => 'What Our Clients Say'],
            ],
            'Latest from Blog' => [
                'blog_title' => ['label' => 'Title', 'text' => 'Latest from Blog'],
                'blog_cta' => ['label' => 'Link label', 'text' => 'View all posts'],
            ],
            'Footer' => [
                'footer_tagline' => ['label' => 'Tagline', 'type' => 'textarea', 'text' => 'The most trusted platform to buy, sell, rent and discover verified properties with confidence.'],
                'footer_company_title' => ['label' => 'Company — Heading', 'text' => 'Company'],
                'footer_about' => ['label' => 'Company — About link', 'text' => 'About Us'],
                'footer_blog' => ['label' => 'Company — Blog link', 'text' => 'Blog'],
                'footer_contact' => ['label' => 'Company — Contact link', 'text' => 'Contact Us'],
                'footer_property_title' => ['label' => 'Property — Heading', 'text' => 'Property'],
                'footer_buy' => ['label' => 'Property — Buy link', 'text' => 'Buy Property'],
                'footer_rent' => ['label' => 'Property — Rent link', 'text' => 'Rent Property'],
                'footer_commercial' => ['label' => 'Property — Commercial link', 'text' => 'Commercial'],
                'footer_new_projects' => ['label' => 'Property — Projects link', 'text' => 'New Projects'],
                'footer_by_location' => ['label' => 'Property — Location link', 'text' => 'Property by Location'],
                'footer_support_title' => ['label' => 'Support — Heading', 'text' => 'Support'],
                'footer_faq' => ['label' => 'Support — FAQ link', 'text' => 'FAQ'],
                'footer_terms' => ['label' => 'Support — Terms link', 'text' => 'Terms & Conditions'],
                'footer_privacy' => ['label' => 'Support — Privacy link', 'text' => 'Privacy Policy'],
                'footer_newsletter_title' => ['label' => 'Newsletter — Heading', 'text' => 'Newsletter'],
                'footer_newsletter_text' => ['label' => 'Newsletter — Text', 'type' => 'textarea', 'text' => 'Subscribe to get the latest properties, offers and real estate news.'],
                'footer_newsletter_placeholder' => ['label' => 'Newsletter — Input placeholder', 'text' => 'Enter your email'],
                'footer_copyright' => ['label' => 'Copyright (year is added automatically)', 'text' => 'Rentdo. All rights reserved.'],
                'footer_made_with' => ['label' => 'Made-with prefix', 'text' => 'Made with'],
                'footer_made_by' => ['label' => 'Made-with suffix', 'text' => 'by Rentdo'],
            ],
        ];
    }

    /**
     * Flat list of every editable field's slug.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_merge(...array_map('array_keys', array_values(static::groups())));
    }

    /**
     * Flat slug => English text (translation key) map, for resolving admin
     * overrides without walking the grouped structure.
     *
     * @return array<string, string>
     */
    public static function fieldsFlat(): array
    {
        $fields = [];

        foreach (static::groups() as $group) {
            foreach ($group as $slug => $meta) {
                $fields[$slug] = $meta['text'];
            }
        }

        return $fields;
    }

    /**
     * Editable image slots grouped by the same section labels as {@see groups()}.
     * Images are global (not per-language) and stored as settings; `default` is
     * the bundled asset path shown until an admin uploads a replacement.
     *
     * @return array<string, array<string, array{label: string, default: string}>>
     */
    public static function imageGroups(): array
    {
        return [
            'Hero Slider' => [
                'home_hero_1_image' => ['label' => 'Slide 1 — Image', 'default' => 'assets/images/website/hero-section/hero-1.webp'],
                'home_hero_2_image' => ['label' => 'Slide 2 — Image', 'default' => 'assets/images/website/hero-section/hero.webp'],
                'home_hero_3_image' => ['label' => 'Slide 3 — Image', 'default' => 'assets/images/website/flat/flat-1.webp'],
            ],
            'Featured Categories' => [
                'home_category_sale_image' => ['label' => 'For Sale — Image', 'default' => 'assets/images/website/features/flat-1.webp'],
                'home_category_rent_image' => ['label' => 'For Rent — Image', 'default' => 'assets/images/website/features/flat-2.webp'],
                'home_category_land_image' => ['label' => 'Land & Commercial — Image', 'default' => 'assets/images/website/flat/flat-1.webp'],
                'home_category_hotel_image' => ['label' => 'Short Stay — Image', 'default' => 'assets/images/website/flat/flat-3.webp'],
                'home_category_mess_image' => ['label' => 'Mess / PG — Image', 'default' => 'assets/images/website/flat/flat-4.webp'],
            ],
            'Property by Location' => [
                'home_location_bg_image' => ['label' => 'Card background image', 'default' => 'assets/images/website/location/location-card-bg.webp'],
            ],
            'App Download Banner' => [
                'home_app_phone_image' => ['label' => 'Phone mock-up image', 'default' => 'assets/images/website/download/phone.webp'],
            ],
        ];
    }

    /**
     * Flat list of every editable image key.
     *
     * @return array<int, string>
     */
    public static function imageKeys(): array
    {
        return array_merge(...array_map('array_keys', array_values(static::imageGroups())));
    }

    /**
     * Image slots for one section label (empty when the section has none).
     *
     * @return array<string, array{label: string, default: string}>
     */
    public static function imagesFor(string $groupLabel): array
    {
        return static::imageGroups()[$groupLabel] ?? [];
    }
}
