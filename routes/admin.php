<?php

use App\Http\Controllers\Admin\AccountDeletionRequestController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AreaLandingPageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\HotelBookingController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\ListingPackageController;
use App\Http\Controllers\Admin\ListingReportController;
use App\Http\Controllers\Admin\MaintenanceRequestController;
use App\Http\Controllers\Admin\ModerationReportController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PlanFeatureController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReferralRuleController;
use App\Http\Controllers\Admin\ReviewModerationController;
use App\Http\Controllers\Admin\DealController;
use App\Http\Controllers\Admin\LegalMatterController;
use App\Http\Controllers\Admin\VerificationCaseController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TechnicianBookingController;
use App\Http\Controllers\Admin\TechnicianCategoryController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Middleware\AdminAuditMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    // ── Guest-only auth routes ──────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');

        Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('password.email');

        Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [ResetPasswordController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('password.update');
    });

    // ── Authenticated admin routes ──────────────────────────────────────────
    Route::middleware(['auth', 'admin', AdminAuditMiddleware::class])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        // ── My Profile (own account — available to every admin) ──────────────────
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::get('search', [DashboardController::class, 'search'])
            ->middleware('permission:dashboard.view')
            ->name('search');

        // Users
        Route::prefix('users')->name('users.')->middleware('permission:users.view')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');

            Route::prefix('deletion-requests')->name('deletion-requests.')->middleware('permission:users.delete')->group(function () {
                Route::get('/', [AccountDeletionRequestController::class, 'index'])->name('index');
                Route::post('/{user}/approve', [AccountDeletionRequestController::class, 'approve'])->name('approve');
                Route::post('/{user}/reject', [AccountDeletionRequestController::class, 'reject'])->name('reject');
            });

            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
        });

        // Roles
        Route::prefix('roles')->name('roles.')->middleware('permission:roles.view')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('create');
            Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('store');
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
        });

        // Listings
        Route::prefix('listings')->name('listings.')->middleware('permission:listings.view')->group(function () {
            Route::get('/', [ListingController::class, 'index'])->name('index');
            Route::get('/export', [ListingController::class, 'export'])->name('export');
            Route::post('/bulk-approve', [ListingController::class, 'bulkApprove'])->middleware('permission:listings.approve')->name('bulk-approve');
            Route::post('/bulk-trash', [ListingController::class, 'bulkTrash'])->middleware('permission:listings.delete')->name('bulk-trash');
            Route::get('/create', [ListingController::class, 'create'])->middleware('permission:listings.create')->name('create');
            Route::post('/', [ListingController::class, 'store'])->middleware('permission:listings.create')->name('store');

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [ListingReportController::class, 'index'])->name('index');
                Route::patch('/{report}/dismiss', [ListingReportController::class, 'dismiss'])->middleware('permission:listings.edit')->name('dismiss');
                Route::patch('/{report}/warn', [ListingReportController::class, 'warn'])->middleware('permission:listings.edit')->name('warn');
                Route::delete('/{report}/remove-listing', [ListingReportController::class, 'removeListing'])->middleware('permission:listings.delete')->name('remove');
            });

            Route::get('/{listing}', [ListingController::class, 'show'])->name('show');
            Route::get('/{listing}/edit', [ListingController::class, 'edit'])->middleware('permission:listings.edit')->name('edit');
            Route::put('/{listing}', [ListingController::class, 'update'])->middleware('permission:listings.edit')->name('update');
            Route::delete('/{listing}/images/{image}', [ListingController::class, 'destroyImage'])->middleware('permission:listings.edit')->scopeBindings()->name('images.destroy');
            Route::delete('/{listing}/videos/{video}', [ListingController::class, 'destroyVideo'])->middleware('permission:listings.edit')->scopeBindings()->name('videos.destroy');
            Route::patch('/{listing}/approve', [ListingController::class, 'approve'])->middleware('permission:listings.approve')->name('approve');
            Route::patch('/{listing}/reject', [ListingController::class, 'reject'])->middleware('permission:listings.approve')->name('reject');
            Route::patch('/{listing}/feature', [ListingController::class, 'feature'])->middleware('permission:listings.edit')->name('feature');
            Route::patch('/{listing}/verify', [ListingController::class, 'verify'])->middleware('permission:listings.edit')->name('verify');
            Route::patch('/{listing}/archive', [ListingController::class, 'archive'])->middleware('permission:listings.edit')->name('archive');
            Route::patch('/{id}/restore', [ListingController::class, 'restore'])->middleware('permission:listings.edit')->name('restore');
            Route::delete('/{id}', [ListingController::class, 'destroy'])->middleware('permission:listings.delete')->name('destroy');
        });

        Route::prefix('custom-fields')->name('custom-fields.')->middleware('permission:listings.edit')->group(function () {
            Route::get('/', [CustomFieldController::class, 'index'])->name('index');
            Route::post('/', [CustomFieldController::class, 'store'])->name('store');
            Route::post('/reorder', [CustomFieldController::class, 'reorder'])->name('reorder');
            Route::put('/{customField}', [CustomFieldController::class, 'update'])->name('update');
            Route::patch('/{customField}/move', [CustomFieldController::class, 'move'])->name('move');
            Route::delete('/{customField}', [CustomFieldController::class, 'destroy'])->name('destroy');
        });

        // Zones
        Route::prefix('zones')->name('zones.')->middleware('permission:zones.view')->group(function () {
            Route::get('/', [ZoneController::class, 'index'])->name('index');
            Route::get('/export', [ZoneController::class, 'export'])->name('export');
            Route::middleware('permission:zones.create')->group(function () {
                Route::get('/create', [ZoneController::class, 'create'])->name('create');
                Route::post('/', [ZoneController::class, 'store'])->name('store');
                Route::post('/bulk', [ZoneController::class, 'bulkStore'])->name('bulk-store');
                Route::post('/import', [ZoneController::class, 'import'])->name('import');
            });
            Route::get('/{zone}/edit', [ZoneController::class, 'edit'])->middleware('permission:zones.edit')->name('edit');
            Route::put('/{zone}', [ZoneController::class, 'update'])->middleware('permission:zones.edit')->name('update');
            Route::delete('/{zone}', [ZoneController::class, 'destroy'])->middleware('permission:zones.delete')->name('destroy');
        });
        // Agencies
        Route::prefix('agencies')->name('agencies.')->middleware('permission:agencies.view')->group(function () {
            Route::get('/', [AgencyController::class, 'index'])->name('index');
            Route::get('/export', [AgencyController::class, 'export'])->name('export');
            Route::middleware('permission:agencies.create')->group(function () {
                Route::get('/create', [AgencyController::class, 'create'])->name('create');
                Route::post('/', [AgencyController::class, 'store'])->name('store');
                Route::post('/import', [AgencyController::class, 'import'])->name('import');
            });
            Route::get('/{agency}', [AgencyController::class, 'show'])->name('show');
            Route::get('/{agency}/edit', [AgencyController::class, 'edit'])->middleware('permission:agencies.edit')->name('edit');
            Route::put('/{agency}', [AgencyController::class, 'update'])->middleware('permission:agencies.edit')->name('update');
            Route::delete('/{agency}', [AgencyController::class, 'destroy'])->middleware('permission:agencies.delete')->name('destroy');
            Route::patch('/{agency}/restore', [AgencyController::class, 'restore'])->middleware('permission:agencies.delete')->withTrashed()->name('restore');

            Route::prefix('{agency}/agents')->name('agents.')->middleware('permission:agencies.edit')->group(function () {
                Route::post('/', [AgentController::class, 'store'])->name('store');
                Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
                Route::delete('/{agent}', [AgentController::class, 'destroy'])->name('destroy');
            });
        });
        Route::prefix('technician-categories')->name('technician-categories.')->middleware('permission:technicians.view')->group(function () {
            Route::get('/', [TechnicianCategoryController::class, 'index'])->name('index');
            Route::post('/reorder', [TechnicianCategoryController::class, 'reorder'])->middleware('permission:technicians.edit')->name('reorder');
            Route::post('/', [TechnicianCategoryController::class, 'store'])->middleware('permission:technicians.create')->name('store');
            Route::put('/{category}', [TechnicianCategoryController::class, 'update'])->middleware('permission:technicians.edit')->name('update');
            Route::delete('/{category}', [TechnicianCategoryController::class, 'destroy'])->middleware('permission:technicians.delete')->name('destroy');
        });

        Route::prefix('technicians')->name('technicians.')->middleware('permission:technicians.view')->group(function () {
            Route::get('/', [TechnicianController::class, 'index'])->name('index');
            Route::get('/suggestions/{zone}', [TechnicianController::class, 'suggestions'])->name('suggestions');
            Route::get('/create', [TechnicianController::class, 'create'])->middleware('permission:technicians.create')->name('create');
            Route::post('/', [TechnicianController::class, 'store'])->middleware('permission:technicians.create')->name('store');
            Route::get('/{technician}', [TechnicianController::class, 'show'])->name('show');
            Route::get('/{technician}/edit', [TechnicianController::class, 'edit'])->middleware('permission:technicians.edit')->name('edit');
            Route::put('/{technician}', [TechnicianController::class, 'update'])->middleware('permission:technicians.edit')->name('update');
            Route::patch('/{technician}/approve', [TechnicianController::class, 'approve'])->middleware('permission:technicians.approve')->name('approve');
            Route::patch('/{technician}/suspend', [TechnicianController::class, 'suspend'])->middleware('permission:technicians.edit')->name('suspend');
            Route::patch('/{technician}/zone', [TechnicianController::class, 'assignZone'])->middleware('permission:technicians.edit')->name('zone');
        });

        Route::prefix('tech-bookings')->name('tech-bookings.')->middleware('permission:technician_bookings.view')->group(function () {
            Route::get('/', [TechnicianBookingController::class, 'index'])->name('index');
            Route::get('/{booking}', [TechnicianBookingController::class, 'show'])->name('show');
            Route::patch('/{booking}', [TechnicianBookingController::class, 'update'])->middleware('permission:technician_bookings.edit')->name('update');
            Route::post('/{booking}/quotes', [TechnicianBookingController::class, 'quote'])->middleware('permission:technician_bookings.edit')->name('quotes.store');
        });

        Route::prefix('maintenance-requests')->name('maintenance-requests.')->middleware('permission:technician_bookings.view')->group(function () {
            Route::get('/', [MaintenanceRequestController::class, 'index'])->name('index');
            Route::patch('/{maintenanceRequest}', [MaintenanceRequestController::class, 'update'])->middleware('permission:technician_bookings.edit')->name('update');
        });

        Route::prefix('hotel-bookings')->name('hotel-bookings.')->middleware('permission:hotel_bookings.view')->group(function () {
            Route::get('/', [HotelBookingController::class, 'index'])->name('index');
            Route::get('/availability/{listingId}', [HotelBookingController::class, 'availability'])->name('availability');
            Route::get('/{booking}', [HotelBookingController::class, 'show'])->name('show');
            Route::patch('/{booking}', [HotelBookingController::class, 'update'])->middleware('permission:hotel_bookings.edit')->name('update');
            Route::post('/{booking}/refund', [HotelBookingController::class, 'refund'])->middleware('permission:payments.edit')->name('refund');
        });

        Route::prefix('plans')->name('plans.')->middleware('permission:plans.view')->group(function () {
            Route::get('/', [SubscriptionPlanController::class, 'index'])->name('index');
            Route::post('/reorder', [SubscriptionPlanController::class, 'reorder'])->middleware('permission:plans.edit')->name('reorder');
            Route::post('/', [SubscriptionPlanController::class, 'store'])->middleware('permission:plans.create')->name('store');
            Route::get('/{plan}', [SubscriptionPlanController::class, 'show'])->name('show');
            Route::put('/{plan}', [SubscriptionPlanController::class, 'update'])->middleware('permission:plans.edit')->name('update');
            Route::patch('/{plan}/toggle', [SubscriptionPlanController::class, 'toggle'])->middleware('permission:plans.edit')->name('toggle');
            Route::delete('/{plan}', [SubscriptionPlanController::class, 'destroy'])->middleware('permission:plans.delete')->name('destroy');
        });

        Route::prefix('plan-features')->name('plan-features.')->middleware('permission:plans.view')->group(function () {
            Route::post('/', [PlanFeatureController::class, 'store'])->middleware('permission:plans.edit')->name('store');
            Route::delete('/{planFeature}', [PlanFeatureController::class, 'destroy'])->middleware('permission:plans.delete')->name('destroy');
        });

        Route::prefix('subscriptions')->name('subscriptions.')->middleware('permission:plans.view')->group(function () {
            Route::get('/', [UserSubscriptionController::class, 'index'])->name('index');
            Route::post('/', [UserSubscriptionController::class, 'store'])->middleware('permission:plans.edit')->name('store');
            Route::patch('/{subscription}/extend', [UserSubscriptionController::class, 'extend'])->middleware('permission:plans.edit')->name('extend');
            Route::patch('/{subscription}/cancel', [UserSubscriptionController::class, 'cancel'])->middleware('permission:plans.edit')->name('cancel');
            Route::post('/{subscription}/refund', [UserSubscriptionController::class, 'refund'])->middleware('permission:payments.edit')->name('refund');
        });

        Route::prefix('packages')->name('packages.')->middleware('permission:listing_packages.view')->group(function () {
            Route::get('/', [ListingPackageController::class, 'index'])->name('index');
            Route::post('/reorder', [ListingPackageController::class, 'reorder'])->middleware('permission:listing_packages.edit')->name('reorder');
            Route::post('/', [ListingPackageController::class, 'store'])->middleware('permission:listing_packages.create')->name('store');
            Route::get('/{package}', [ListingPackageController::class, 'show'])->name('show');
            Route::put('/{package}', [ListingPackageController::class, 'update'])->middleware('permission:listing_packages.edit')->name('update');
            Route::patch('/{package}/toggle', [ListingPackageController::class, 'toggle'])->middleware('permission:listing_packages.edit')->name('toggle');
            Route::delete('/{package}', [ListingPackageController::class, 'destroy'])->middleware('permission:listing_packages.delete')->name('destroy');
        });

        Route::prefix('coupons')->name('coupons.')->middleware('permission:coupons.view')->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('index');
            Route::post('/', [CouponController::class, 'store'])->middleware('permission:coupons.create')->name('store');
            Route::get('/{coupon}', [CouponController::class, 'show'])->name('show');
            Route::put('/{coupon}', [CouponController::class, 'update'])->middleware('permission:coupons.edit')->name('update');
            Route::delete('/{coupon}', [CouponController::class, 'destroy'])->middleware('permission:coupons.delete')->name('destroy');
        });

        Route::prefix('wallets')->name('wallets.')->middleware('permission:payments.view')->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::get('/{wallet}', [WalletController::class, 'show'])->name('show');
            Route::post('/users/{user}/adjust', [WalletController::class, 'adjust'])->middleware('permission:payments.edit')->name('adjust');
        });

        // ── Payments & Finance ────────────────────────────────────────────────────
        Route::prefix('payments')->name('payments.')->middleware('permission:payments.view')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/export', [PaymentController::class, 'exportCsv'])->name('export');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        });

        Route::prefix('refunds')->name('refunds.')->middleware('permission:payments.view')->group(function () {
            Route::get('/', [PaymentController::class, 'refundsIndex'])->name('index');
            Route::patch('/{refund}', [PaymentController::class, 'processRefund'])->middleware('permission:payments.edit')->name('process');
        });

        Route::prefix('payouts')->name('payouts.')->middleware('permission:payments.view')->group(function () {
            Route::get('/', [PayoutController::class, 'index'])->name('index');
            Route::post('/', [PayoutController::class, 'store'])->middleware('permission:payments.edit')->name('store');
            Route::patch('/{payout}/status', [PayoutController::class, 'updateStatus'])->middleware('permission:payments.edit')->name('status');
        });

        Route::prefix('invoices')->name('invoices.')->middleware('permission:payments.view')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::post('/generate', [InvoiceController::class, 'generateFromPayment'])->middleware('permission:payments.edit')->name('generate');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        });

        // ── Exports (reusable across every listing) ────────────────────────────────
        // Per-export permission is enforced in the controller from each profile, so the
        // group itself only requires an authenticated admin. Model-bound routes are pinned
        // to numeric ids so they never shadow the string {resource} routes.
        Route::prefix('exports')->name('exports.')->group(function () {
            Route::get('/', [ExportController::class, 'index'])->name('index');
            Route::get('/{export}/progress', [ExportController::class, 'progress'])->whereNumber('export')->name('progress');
            Route::get('/{export}/download', [ExportController::class, 'download'])->whereNumber('export')->name('download');
            Route::delete('/{export}', [ExportController::class, 'destroy'])->whereNumber('export')->name('destroy');
            Route::get('/{resource}/options', [ExportController::class, 'options'])->name('options');
            Route::get('/{resource}/print', [ExportController::class, 'print'])->name('print');
            Route::post('/{resource}', [ExportController::class, 'store'])->name('store');
        });

        // ── Admin Notifications ────────────────────────────────────────────────────
        Route::prefix('notifications')->name('notifications.')->middleware('permission:notifications.view')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::get('/feed', [AdminNotificationController::class, 'feed'])->name('feed');
            Route::patch('/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('read');
            Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])->name('mark-all-read');
            Route::get('/preferences', [AdminNotificationController::class, 'preferences'])->middleware('permission:notifications.edit')->name('preferences');
            Route::post('/preferences', [AdminNotificationController::class, 'updatePreferences'])->middleware('permission:notifications.edit')->name('preferences.update');
        });

        Route::prefix('verification')->name('verification.')->middleware('permission:users.approve')->group(function () {
            Route::get('/', [VerificationController::class, 'index'])->name('index');
            Route::get('/{user}/document', [VerificationController::class, 'document'])->name('document');
            Route::patch('/{user}', [VerificationController::class, 'update'])->name('update');
        });

        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [ModerationReportController::class, 'index'])->name('index');
            Route::patch('/{report}/action', [ModerationReportController::class, 'action'])->middleware('permission:reports.edit')->name('action');
        });

        Route::prefix('disputes')->name('disputes.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [DisputeController::class, 'index'])->name('index');
            Route::patch('/{dispute}', [DisputeController::class, 'update'])->middleware('permission:reports.edit')->name('update');
        });

        Route::prefix('reviews')->name('reviews.')->middleware('permission:reviews.view')->group(function () {
            Route::get('/', [ReviewModerationController::class, 'index'])->name('index');
            Route::patch('/{review}/moderate', [ReviewModerationController::class, 'moderate'])->middleware('permission:reviews.edit')->name('moderate');
        });

        Route::prefix('verification-cases')->name('verification-cases.')->middleware('permission:verification_cases.view')->group(function () {
            Route::get('/', [VerificationCaseController::class, 'index'])->name('index');
            Route::get('/{case}', [VerificationCaseController::class, 'show'])->name('show');
            Route::patch('/{case}/assign', [VerificationCaseController::class, 'assign'])->middleware('permission:verification_cases.assign')->name('assign');
            Route::patch('/{case}/tasks/{task}', [VerificationCaseController::class, 'updateTask'])->middleware('permission:verification_tasks.update')->scopeBindings()->name('tasks.update');
            Route::post('/{case}/tasks/{task}/evidence', [VerificationCaseController::class, 'uploadEvidence'])->middleware('permission:verification_tasks.update')->scopeBindings()->name('tasks.evidence');
            Route::get('/evidence/{evidence}/file', [VerificationCaseController::class, 'evidenceFile'])->name('evidence.file');
        });

        // ── Deals & Transactions ───────────────────────────────────────────────
        Route::prefix('deals')->name('deals.')->middleware('permission:deals.view')->group(function () {
            Route::get('/', [DealController::class, 'index'])->name('index');
            Route::get('/{deal}', [DealController::class, 'show'])->name('show');
            Route::patch('/{deal}/advance', [DealController::class, 'advance'])->middleware('permission:deals.advance')->name('advance');
            Route::patch('/{deal}/cancel', [DealController::class, 'cancel'])->middleware('permission:deals.cancel')->name('cancel');
        });

        // ── Legal Matters ──────────────────────────────────────────────────────
        Route::prefix('legal-matters')->name('legal-matters.')->middleware('permission:legal_matters.view')->group(function () {
            Route::get('/', [LegalMatterController::class, 'index'])->name('index');
            Route::get('/{legalMatter}', [LegalMatterController::class, 'show'])->name('show');
            Route::patch('/{legalMatter}', [LegalMatterController::class, 'update'])->middleware('permission:legal_matters.edit')->name('update');
        });

        // ── CMS Pages ────────────────────────────────────────────────────────────
        Route::prefix('cms')->name('cms.')->middleware('permission:cms.view')->group(function () {
            Route::get('/', [CmsPageController::class, 'index'])->name('index');
            Route::post('/', [CmsPageController::class, 'store'])->middleware('permission:cms.create')->name('store');
            Route::get('/{cmsPage}', [CmsPageController::class, 'show'])->name('show');
            Route::put('/{cmsPage}', [CmsPageController::class, 'update'])->middleware('permission:cms.edit')->name('update');
            Route::delete('/{cmsPage}', [CmsPageController::class, 'destroy'])->middleware('permission:cms.delete')->name('destroy');
        });

        // ── FAQs ──────────────────────────────────────────────────────────────────
        Route::prefix('faqs')->name('faqs.')->middleware('permission:cms.view')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::post('/reorder', [FaqController::class, 'reorder'])->middleware('permission:cms.edit')->name('reorder');
            Route::post('/', [FaqController::class, 'store'])->middleware('permission:cms.create')->name('store');
            Route::put('/{faq}', [FaqController::class, 'update'])->middleware('permission:cms.edit')->name('update');
            Route::delete('/{faq}', [FaqController::class, 'destroy'])->middleware('permission:cms.delete')->name('destroy');
        });

        // ── Blog ──────────────────────────────────────────────────────────────────
        Route::prefix('blogs')->name('blogs.')->middleware('permission:blogs.view')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::post('/reorder', [BlogController::class, 'reorder'])->middleware('permission:blogs.edit')->name('reorder');
            Route::post('/', [BlogController::class, 'store'])->middleware('permission:blogs.create')->name('store');

            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [BlogCategoryController::class, 'index'])->name('index');
                Route::post('/reorder', [BlogCategoryController::class, 'reorder'])->middleware('permission:blogs.edit')->name('reorder');
                Route::post('/', [BlogCategoryController::class, 'store'])->middleware('permission:blogs.create')->name('store');
                Route::put('/{blogCategory}', [BlogCategoryController::class, 'update'])->middleware('permission:blogs.edit')->name('update');
                Route::delete('/{blogCategory}', [BlogCategoryController::class, 'destroy'])->middleware('permission:blogs.delete')->name('destroy');
            });

            Route::get('/{blog}', [BlogController::class, 'show'])->name('show');
            Route::put('/{blog}', [BlogController::class, 'update'])->middleware('permission:blogs.edit')->name('update');
            Route::delete('/{blog}', [BlogController::class, 'destroy'])->middleware('permission:blogs.delete')->name('destroy');
        });

        // ── SEO Meta ──────────────────────────────────────────────────────────────
        Route::prefix('seo')->name('seo.')->middleware('permission:seo.view')->group(function () {
            Route::get('/', [SeoMetaController::class, 'index'])->name('index');
            Route::get('/records', [SeoMetaController::class, 'records'])->name('records');
            Route::post('/', [SeoMetaController::class, 'store'])->middleware('permission:seo.edit')->name('store');
            Route::get('/{seoMeta}', [SeoMetaController::class, 'show'])->name('show');
            Route::put('/{seoMeta}', [SeoMetaController::class, 'update'])->middleware('permission:seo.edit')->name('update');
            Route::delete('/{seoMeta}', [SeoMetaController::class, 'destroy'])->middleware('permission:seo.edit')->name('destroy');
        });

        // ── Area Landing Pages ────────────────────────────────────────────────────
        Route::prefix('area-landing-pages')->name('area-landing-pages.')->middleware('permission:seo.view')->group(function () {
            Route::get('zones', [AreaLandingPageController::class, 'zones'])->name('zones');
            Route::get('/', [AreaLandingPageController::class, 'index'])->name('index');
            Route::post('/', [AreaLandingPageController::class, 'store'])->middleware('permission:seo.edit')->name('store');
            Route::get('/{areaLandingPage}', [AreaLandingPageController::class, 'show'])->name('show');
            Route::put('/{areaLandingPage}', [AreaLandingPageController::class, 'update'])->middleware('permission:seo.edit')->name('update');
            Route::delete('/{areaLandingPage}', [AreaLandingPageController::class, 'destroy'])->middleware('permission:seo.edit')->name('destroy');
        });

        // ── Banners / Ad Slots ────────────────────────────────────────────────────
        Route::prefix('banners')->name('banners.')->middleware('permission:banners.view')->group(function () {
            Route::get('/', [BannerController::class, 'index'])->name('index');
            Route::post('/reorder', [BannerController::class, 'reorder'])->middleware('permission:banners.edit')->name('reorder');
            Route::post('/', [BannerController::class, 'store'])->middleware('permission:banners.create')->name('store');
            Route::get('/{banner}', [BannerController::class, 'show'])->name('show');
            Route::put('/{banner}', [BannerController::class, 'update'])->middleware('permission:banners.edit')->name('update');
            Route::delete('/{banner}', [BannerController::class, 'destroy'])->middleware('permission:banners.delete')->name('destroy');
        });

        // ── Testimonials ─────────────────────────────────────────────────────────
        Route::prefix('testimonials')->name('testimonials.')->middleware('permission:testimonials.view')->group(function () {
            Route::get('/', [TestimonialController::class, 'index'])->name('index');
            Route::post('/reorder', [TestimonialController::class, 'reorder'])->middleware('permission:testimonials.edit')->name('reorder');
            Route::post('/', [TestimonialController::class, 'store'])->middleware('permission:testimonials.create')->name('store');
            Route::get('/{testimonial}', [TestimonialController::class, 'show'])->name('show');
            Route::put('/{testimonial}', [TestimonialController::class, 'update'])->middleware('permission:testimonials.edit')->name('update');
            Route::delete('/{testimonial}', [TestimonialController::class, 'destroy'])->middleware('permission:testimonials.delete')->name('destroy');
        });

        // ── Projects ─────────────────────────────────────────────────────────────
        Route::prefix('projects')->name('projects.')->middleware('permission:projects.view')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::post('/', [ProjectController::class, 'store'])->middleware('permission:projects.create')->name('store');

            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [ProjectCategoryController::class, 'index'])->name('index');
                Route::post('/reorder', [ProjectCategoryController::class, 'reorder'])->middleware('permission:projects.edit')->name('reorder');
                Route::post('/', [ProjectCategoryController::class, 'store'])->middleware('permission:projects.create')->name('store');
                Route::put('/{projectCategory}', [ProjectCategoryController::class, 'update'])->middleware('permission:projects.edit')->name('update');
                Route::delete('/{projectCategory}', [ProjectCategoryController::class, 'destroy'])->middleware('permission:projects.delete')->name('destroy');
            });

            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::put('/{project}', [ProjectController::class, 'update'])->middleware('permission:projects.edit')->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->middleware('permission:projects.delete')->name('destroy');
        });

        // ── Contact Messages ─────────────────────────────────────────────────────
        Route::prefix('contact-messages')->name('contact-messages.')->middleware('permission:contact_messages.view')->group(function () {
            Route::get('/', [ContactMessageController::class, 'index'])->name('index');
            Route::patch('/{contactMessage}', [ContactMessageController::class, 'updateStatus'])->middleware('permission:contact_messages.edit')->name('status');
        });

        // ── Marketing ─────────────────────────────────────────────────────────────
        Route::prefix('referral-rules')->name('referral-rules.')->middleware('permission:marketing.view')->group(function () {
            Route::get('/', [ReferralRuleController::class, 'index'])->name('index');
            Route::post('/', [ReferralRuleController::class, 'store'])->middleware('permission:marketing.edit')->name('store');
            Route::get('/{referralRule}', [ReferralRuleController::class, 'show'])->name('show');
            Route::put('/{referralRule}', [ReferralRuleController::class, 'update'])->middleware('permission:marketing.edit')->name('update');
            Route::delete('/{referralRule}', [ReferralRuleController::class, 'destroy'])->middleware('permission:marketing.edit')->name('destroy');
        });

        Route::prefix('campaigns')->name('campaigns.')->middleware('permission:marketing.view')->group(function () {
            Route::get('segments', [CampaignController::class, 'segments'])->name('segments');
            Route::get('templates', [CampaignController::class, 'templates'])->name('templates');
            Route::get('/', [CampaignController::class, 'index'])->name('index');
            Route::post('/', [CampaignController::class, 'store'])->middleware('permission:marketing.edit')->name('store');
            Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
            Route::put('/{campaign}', [CampaignController::class, 'update'])->middleware('permission:marketing.edit')->name('update');
            Route::post('/{campaign}/send', [CampaignController::class, 'send'])->middleware('permission:marketing.edit')->name('send');
            Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->middleware('permission:marketing.edit')->name('destroy');
        });

        // ── Settings ───────────────────────────────────────────────────────────
        Route::get('settings', [SettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->middleware('permission:settings.edit')->name('settings.update');
        Route::post('settings/homepage', [SettingController::class, 'updateHomepage'])->middleware('permission:settings.edit')->name('settings.homepage');

        // Languages
        Route::resource('languages', LanguageController::class)
            ->except('show')
            ->middleware('permission:settings.edit');

        // Translations
        Route::get('translations', [TranslationController::class, 'index'])->middleware('permission:settings.view')->name('translations.index');
        Route::post('translations', [TranslationController::class, 'store'])->middleware('permission:settings.edit')->name('translations.store');
        Route::put('translations/{translation}', [TranslationController::class, 'update'])->middleware('permission:settings.edit')->name('translations.update');
        Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])->middleware('permission:settings.edit')->name('translations.destroy');

        // Currencies
        Route::resource('currencies', CurrencyController::class)
            ->except('show')
            ->middleware('permission:settings.edit');

        // Email templates
        Route::get('email-templates', [EmailTemplateController::class, 'index'])->middleware('permission:settings.view')->name('email-templates.index');
        Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->middleware('permission:settings.edit')->name('email-templates.edit');
        Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->middleware('permission:settings.edit')->name('email-templates.update');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view')->name('audit-logs.index');
    });
});
