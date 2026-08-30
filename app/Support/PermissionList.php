<?php

namespace App\Support;

class PermissionList
{
    public static function all(): array
    {
        return array_merge(...array_values(static::grouped()));
    }

    /** @return array<string, list<string>> */
    public static function grouped(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view',
            ],
            'Users' => [
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.approve',
            ],
            'Roles & Permissions' => [
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            ],
            'Listings' => [
                'listings.view', 'listings.create', 'listings.edit', 'listings.delete', 'listings.approve',
            ],
            'Zones' => [
                'zones.view', 'zones.create', 'zones.edit', 'zones.delete',
            ],
            'Agencies' => [
                'agencies.view', 'agencies.create', 'agencies.edit', 'agencies.delete', 'agencies.approve',
            ],
            'Technicians' => [
                'technicians.view', 'technicians.create', 'technicians.edit', 'technicians.delete', 'technicians.approve',
            ],
            'Technician Bookings' => [
                'technician_bookings.view', 'technician_bookings.edit',
            ],
            'Hotel Bookings' => [
                'hotel_bookings.view', 'hotel_bookings.edit',
            ],
            'Subscription Plans' => [
                'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            ],
            'Listing Packages' => [
                'listing_packages.view', 'listing_packages.create', 'listing_packages.edit', 'listing_packages.delete',
            ],
            'Coupons' => [
                'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
            ],
            'Payments' => [
                'payments.view', 'payments.edit',
            ],
            'Reviews' => [
                'reviews.view', 'reviews.edit', 'reviews.delete',
            ],
            'Verification Cases' => [
                'verification_cases.view', 'verification_cases.assign', 'verification_cases.finalize', 'verification_tasks.update',
            ],
            'Deals & Transactions' => [
                'deals.view', 'deals.edit', 'deals.advance', 'deals.cancel',
            ],
            'Offers' => [
                'offers.view', 'offers.edit',
            ],
            'Legal Matters' => [
                'legal_matters.view', 'legal_matters.edit', 'legal_matters.resolve',
            ],
            'Inspections' => [
                'inspections.view', 'inspections.assign',
            ],
            'Reports' => [
                'reports.view', 'reports.edit', 'reports.delete',
            ],
            'CMS Pages' => [
                'cms.view', 'cms.create', 'cms.edit', 'cms.delete',
            ],
            'Blogs' => [
                'blogs.view', 'blogs.create', 'blogs.edit', 'blogs.delete',
            ],
            'Settings' => [
                'settings.view', 'settings.edit',
            ],
            'Audit Logs' => [
                'audit_logs.view',
            ],
            'SEO & Landing Pages' => [
                'seo.view', 'seo.edit',
            ],
            'Banners / Ad Slots' => [
                'banners.view', 'banners.create', 'banners.edit', 'banners.delete',
            ],
            'Testimonials' => [
                'testimonials.view', 'testimonials.create', 'testimonials.edit', 'testimonials.delete',
            ],
            'Projects' => [
                'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
            ],
            'Contact Messages' => [
                'contact_messages.view', 'contact_messages.edit',
            ],
            'Marketing' => [
                'marketing.view', 'marketing.edit',
            ],
            'Notifications' => [
                'notifications.view', 'notifications.edit',
            ],
        ];
    }
}
