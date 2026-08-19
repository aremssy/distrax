<?php

use App\Support\Exports\Profiles;

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    | Generated export files are written to this disk under this directory, and
    | pruned once they are older than the retention window.
    */

    'disk' => env('EXPORT_DISK', 'local'),
    'directory' => 'exports',
    'retention_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Queueing
    |--------------------------------------------------------------------------
    | Exports whose row count exceeds this threshold are pushed onto the queue and
    | processed in the background with progress reporting. Smaller ones (and the
    | "selected"/"page" scopes) run synchronously within the request.
    */

    'queue_threshold' => (int) env('EXPORT_QUEUE_THRESHOLD', 2000),

    /*
    | Hard row cap for PDF exports — dompdf renders the whole table in memory, so a
    | runaway "export everything to PDF" is capped rather than exhausting memory.
    */

    'pdf_max_rows' => (int) env('EXPORT_PDF_MAX_ROWS', 5000),

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    | Maps an export slug to the ExportProfile that describes it. Each listing page
    | that offers exports has one entry here.
    */

    'profiles' => [
        // People & access
        'users' => Profiles\UsersExportProfile::class,
        'verification' => Profiles\VerificationExportProfile::class,
        'roles' => Profiles\RoleExportProfile::class,
        'audit-logs' => Profiles\AuditLogExportProfile::class,

        // Listings & real estate
        'listings' => Profiles\ListingsExportProfile::class,
        'agencies' => Profiles\AgencyExportProfile::class,
        'zones' => Profiles\ZoneExportProfile::class,
        'projects' => Profiles\ProjectExportProfile::class,
        'project-categories' => Profiles\ProjectCategoryExportProfile::class,
        'area-landing-pages' => Profiles\AreaLandingPageExportProfile::class,
        'custom-fields' => Profiles\CustomFieldExportProfile::class,

        // Bookings & services
        'hotel-bookings' => Profiles\HotelBookingExportProfile::class,
        'tech-bookings' => Profiles\TechnicianBookingExportProfile::class,
        'technicians' => Profiles\TechnicianExportProfile::class,
        'technician-categories' => Profiles\TechnicianCategoryExportProfile::class,
        'maintenance-requests' => Profiles\MaintenanceRequestExportProfile::class,

        // Finance
        'payments' => Profiles\PaymentsExportProfile::class,
        'payouts' => Profiles\PayoutExportProfile::class,
        'refunds' => Profiles\RefundExportProfile::class,
        'invoices' => Profiles\InvoiceExportProfile::class,
        'wallets' => Profiles\WalletExportProfile::class,
        'subscriptions' => Profiles\UserSubscriptionExportProfile::class,
        'plans' => Profiles\SubscriptionPlanExportProfile::class,
        'packages' => Profiles\ListingPackageExportProfile::class,
        'coupons' => Profiles\CouponExportProfile::class,
        'referral-rules' => Profiles\ReferralRuleExportProfile::class,

        // Engagement & moderation
        'disputes' => Profiles\DisputeExportProfile::class,
        'reviews' => Profiles\ReviewExportProfile::class,
        'testimonials' => Profiles\TestimonialExportProfile::class,
        'contact-messages' => Profiles\ContactMessageExportProfile::class,

        // Content & CMS
        'blogs' => Profiles\BlogExportProfile::class,
        'blog-categories' => Profiles\BlogCategoryExportProfile::class,
        'cms' => Profiles\CmsPageExportProfile::class,
        'banners' => Profiles\BannerExportProfile::class,
        'faqs' => Profiles\FaqExportProfile::class,
        'seo' => Profiles\SeoMetaExportProfile::class,

        // System & localisation
        'translations' => Profiles\TranslationExportProfile::class,
        'email-templates' => Profiles\EmailTemplateExportProfile::class,
        'languages' => Profiles\LanguageExportProfile::class,
        'currencies' => Profiles\CurrencyExportProfile::class,
    ],
];
