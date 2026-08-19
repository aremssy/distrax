<?php

namespace App\Services;

class AdminMenu
{
    /**
     * The admin sidebar navigation. Divider entries mark section headings.
     * Consumed by the sidebar partial and by the global search so admins
     * can jump to any page by typing its name.
     *
     * @return array<int, array{divider?: bool, label: string, route?: string, permission?: string, icon?: string}>
     */
    public static function items(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'permission' => 'dashboard.view', 'icon' => 'squares-2x2'],
            ['divider' => true, 'label' => 'Listings'],
            ['label' => 'All Listings',   'route' => 'admin.listings.index',  'permission' => 'listings.view',  'icon' => 'building-office-2'],
            ['label' => 'Listing Reports', 'route' => 'admin.listings.reports.index', 'permission' => 'listings.view', 'icon' => 'flag'],
            ['label' => 'Custom Fields',  'route' => 'admin.custom-fields.index', 'permission' => 'listings.edit', 'icon' => 'adjustments'],
            ['label' => 'Zones',          'route' => 'admin.zones.index',     'permission' => 'zones.view',     'icon' => 'map-pin'],
            ['label' => 'Agencies',       'route' => 'admin.agencies.index',  'permission' => 'agencies.view',  'icon' => 'building-office'],
            ['divider' => true, 'label' => 'Users & Trust'],
            ['label' => 'Users',          'route' => 'admin.users.index',     'permission' => 'users.view',     'icon' => 'users'],
            ['label' => 'Deletion Requests', 'route' => 'admin.users.deletion-requests.index', 'permission' => 'users.delete', 'icon' => 'trash'],
            ['label' => 'Reviews',        'route' => 'admin.reviews.index',   'permission' => 'reviews.view',   'icon' => 'star'],
            ['label' => 'Reports',        'route' => 'admin.reports.index',   'permission' => 'reports.view',   'icon' => 'flag'],
            ['divider' => true, 'label' => 'Services'],
            ['label' => 'Technicians',    'route' => 'admin.technicians.index',   'permission' => 'technicians.view',        'icon' => 'wrench-screwdriver'],
            ['label' => 'Tech Bookings',  'route' => 'admin.tech-bookings.index', 'permission' => 'technician_bookings.view', 'icon' => 'calendar-days'],
            ['label' => 'Hotel Bookings', 'route' => 'admin.hotel-bookings.index', 'permission' => 'hotel_bookings.view',     'icon' => 'key'],
            ['divider' => true, 'label' => 'Commerce'],
            ['label' => 'Sub. Plans',     'route' => 'admin.plans.index',     'permission' => 'plans.view',             'icon' => 'credit-card'],
            ['label' => 'Packages',       'route' => 'admin.packages.index',  'permission' => 'listing_packages.view',  'icon' => 'cube'],
            ['label' => 'Coupons',        'route' => 'admin.coupons.index',   'permission' => 'coupons.view',           'icon' => 'tag'],
            ['label' => 'Payments',       'route' => 'admin.payments.index',  'permission' => 'payments.view',          'icon' => 'banknotes'],
            ['label' => 'Refunds',        'route' => 'admin.refunds.index',   'permission' => 'payments.view',          'icon' => 'arrow-uturn-left'],
            ['label' => 'Payouts',        'route' => 'admin.payouts.index',   'permission' => 'payments.view',          'icon' => 'currency-dollar'],
            ['divider' => true, 'label' => 'Content'],
            ['label' => 'CMS Pages',      'route' => 'admin.cms.index',       'permission' => 'cms.view',   'icon' => 'document-text'],
            ['label' => 'FAQs',           'route' => 'admin.faqs.index',      'permission' => 'cms.view',   'icon' => 'question-mark-circle'],
            ['label' => 'Blog',           'route' => 'admin.blogs.index',     'permission' => 'blogs.view', 'icon' => 'newspaper'],
            ['divider' => true, 'label' => 'SEO & Marketing'],
            ['label' => 'SEO Meta',       'route' => 'admin.seo.index',       'permission' => 'seo.view',   'icon' => 'magnifying-glass-circle'],
            ['label' => 'Area Landing',   'route' => 'admin.area-landing-pages.index', 'permission' => 'seo.view', 'icon' => 'map'],
            ['label' => 'Banners',        'route' => 'admin.banners.index',   'permission' => 'banners.view', 'icon' => 'photo'],
            ['label' => 'Testimonials',   'route' => 'admin.testimonials.index', 'permission' => 'testimonials.view', 'icon' => 'star'],
            ['label' => 'Projects',       'route' => 'admin.projects.index',  'permission' => 'projects.view', 'icon' => 'building-office-2'],
            ['label' => 'Contact Messages', 'route' => 'admin.contact-messages.index', 'permission' => 'contact_messages.view', 'icon' => 'envelope'],
            ['label' => 'Referral Rules', 'route' => 'admin.referral-rules.index', 'permission' => 'marketing.view', 'icon' => 'share'],
            ['label' => 'Campaigns',      'route' => 'admin.campaigns.index', 'permission' => 'marketing.view', 'icon' => 'megaphone'],
            ['divider' => true, 'label' => 'System'],
            ['label' => 'Roles',          'route' => 'admin.roles.index',     'permission' => 'roles.view',      'icon' => 'shield-check'],
            ['label' => 'Settings',       'route' => 'admin.settings.index',  'permission' => 'settings.view',   'icon' => 'cog-6-tooth'],
            ['label' => 'Audit Logs',     'route' => 'admin.audit-logs.index', 'permission' => 'audit_logs.view', 'icon' => 'clipboard-document-list'],
        ];
    }

    /**
     * Icon paths needed to render global search results: every sidebar icon
     * plus the icons used by entity result groups.
     *
     * @return array<string, string>
     */
    public static function searchIconPaths(): array
    {
        $names = collect(self::items())
            ->pluck('icon')
            ->filter()
            ->merge(['users', 'building-office-2', 'banknotes', 'wrench-screwdriver', 'newspaper', 'magnifying-glass'])
            ->unique()
            ->all();

        return array_intersect_key(AdminIcons::PATHS, array_flip($names));
    }
}
