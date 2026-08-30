<?php

use App\Http\Controllers\InstallerController;
use App\Http\Controllers\Website\AccountController;
use App\Http\Controllers\Website\AskDistraxController;
use App\Http\Controllers\Website\AgencyTeamController;
use App\Http\Controllers\Website\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Website\Auth\ForgotPasswordController;
use App\Http\Controllers\Website\Auth\RegisteredUserController;
use App\Http\Controllers\Website\Auth\ResetPasswordController;
use App\Http\Controllers\Website\BlogController;
use App\Http\Controllers\Website\CheckoutController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\DashboardController;
use App\Http\Controllers\Website\EngagementController;
use App\Http\Controllers\Website\FeedbackController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\MaintenanceRequestController;
use App\Http\Controllers\Website\MarketplaceController;
use App\Http\Controllers\Website\MessageController;
use App\Http\Controllers\Website\NewsletterController;
use App\Http\Controllers\Website\NotificationController;
use App\Http\Controllers\Website\OwnerLeadsController;
use App\Http\Controllers\Website\OwnerListingController;
use App\Http\Controllers\Website\PageController;
use App\Http\Controllers\Website\PreferenceController;
use App\Http\Controllers\Website\ProjectController;
use App\Http\Controllers\Website\OfferController;
use App\Http\Controllers\Website\InspectionController;
use App\Http\Controllers\Website\InstitutionalController;
use App\Http\Controllers\Website\EscrowInvestController;
use App\Http\Controllers\Website\PropertyController;
use App\Http\Controllers\Website\VerificationPassportController;
use App\Http\Controllers\Website\RentManagement\AgreementController as WebsiteAgreementController;
use App\Http\Controllers\Website\RentManagement\LedgerController as WebsiteLedgerController;
use App\Http\Controllers\Website\RentManagement\RentManagementController;
use App\Http\Controllers\Website\RentManagement\RentPaymentController as WebsiteRentPaymentController;
use App\Http\Controllers\Website\RentManagement\TenancyController as WebsiteTenancyController;
use App\Http\Controllers\Website\RentManagement\UnitController as WebsiteUnitController;
use App\Http\Controllers\Website\SubscriptionController;
use App\Http\Controllers\Website\TechnicianApplicationController;
use App\Http\Controllers\Website\TechnicianDashboardController;
use App\Http\Controllers\Website\TrustController;
use App\Http\Controllers\Website\VisitController;
use App\Http\Controllers\Website\ZoneController;
use App\Http\Middleware\RedirectIfInstalled;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', HomeController::class)->name('home');

// ── Crawler discovery: robots.txt + sitemap.xml at their conventional paths ────
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /install',
        'Disallow: /dashboard',
        'Disallow: /login',
        'Disallow: /register',
        'Disallow: /password',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/sitemap.xml', function () {
    abort_unless(Storage::disk('public')->exists('sitemap.xml'), 404);

    return response(Storage::disk('public')->get('sitemap.xml'), 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');

// Slug-based public URLs. Legacy numeric-ID URLs are 301-redirected to the
// canonical slug inside each controller action (a numeric segment can't be a slug).
Route::get('/properties/{property:slug}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/properties/{property:slug}/distance', [PropertyController::class, 'distance'])->name('properties.distance');
Route::post('/properties/{property:slug}/ask-distrax', [AskDistraxController::class, 'ask'])->name('properties.ask-distrax')->middleware('throttle:20,1');
Route::get('/verify/{reference}', [VerificationPassportController::class, 'show'])->name('verify.show');
Route::get('/technicians', [MarketplaceController::class, 'technicians'])->name('technicians.index');
Route::get('/technicians/{technician:slug}', [MarketplaceController::class, 'technician'])->name('technicians.show');
Route::get('/agencies', [MarketplaceController::class, 'agencies'])->name('agencies.index');
Route::get('/agencies/{agency:slug}', [MarketplaceController::class, 'agency'])->name('agencies.show');
Route::get('/agents', [MarketplaceController::class, 'agents'])->name('agents.index');
Route::get('/agents/{agent:slug}', [MarketplaceController::class, 'agent'])->name('agents.show');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/areas/{zone:slug}', [ZoneController::class, 'landing'])->name('areas.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::post('/zone-selection', [ZoneController::class, 'select'])->name('zone.select');
Route::post('/zone-selection/resolve', [ZoneController::class, 'resolve'])->name('zone.resolve');

Route::post('/locale-selection', [PreferenceController::class, 'setLocale'])->name('locale.select');
Route::post('/currency-selection', [PreferenceController::class, 'setCurrency'])->name('currency.select');
Route::post('/low-data-mode', [PreferenceController::class, 'toggleLowData'])->name('low-data.toggle');

Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,1')->name('newsletter.store');
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:20,1')->name('feedback.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/favorites', [EngagementController::class, 'index'])->name('dashboard.wishlist');
        Route::get('/messages', [MessageController::class, 'index'])->name('dashboard.messages');
        Route::get('/messages/{conversation}', [MessageController::class, 'index'])->name('dashboard.messages.show');
        Route::get('/wallet', [SubscriptionController::class, 'index'])->name('dashboard.wallet');
        Route::get('/checkout/{payment}', [CheckoutController::class, 'start'])->name('checkout.start');
        Route::get('/activity', [VisitController::class, 'index'])->name('dashboard.activity');
        Route::get('/profile', [AccountController::class, 'profile'])->name('dashboard.profile');
        Route::put('/profile', [AccountController::class, 'update'])->name('dashboard.profile.update');
        Route::post('/profile/verification', [AccountController::class, 'verification'])->name('dashboard.profile.verification');
        Route::get('/settings', [AccountController::class, 'settings'])->name('dashboard.settings');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('dashboard.notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('dashboard.notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('dashboard.notifications.read');
        Route::get('/compare', [EngagementController::class, 'compareIndex'])->name('dashboard.compare');
        Route::get('/reports', [TrustController::class, 'myReports'])->name('dashboard.reports');

        Route::prefix('maintenance-requests')->name('dashboard.maintenance.')->group(function () {
            Route::get('/', [MaintenanceRequestController::class, 'index'])->name('index');
            Route::get('/create', [MaintenanceRequestController::class, 'create'])->name('create');
            Route::post('/', [MaintenanceRequestController::class, 'store'])->name('store');
            Route::patch('/{maintenanceRequest}', [MaintenanceRequestController::class, 'update'])->name('update');
        });
    });

    Route::post('/properties/{property}/favorite', [EngagementController::class, 'storeFavorite'])->name('properties.favorite.store');
    Route::delete('/properties/{property}/favorite', [EngagementController::class, 'destroyFavorite'])->name('properties.favorite.destroy');
    Route::post('/properties/{property}/reveal-contact', [EngagementController::class, 'revealContact'])->name('properties.reveal-contact');
    Route::post('/properties/{property}/compare', [EngagementController::class, 'storeCompare'])->name('properties.compare.store');
    Route::delete('/properties/{property}/compare', [EngagementController::class, 'destroyCompare'])->name('properties.compare.destroy');
    Route::delete('/compare', [EngagementController::class, 'clearCompare'])->name('compare.clear');
    Route::post('/properties/{property}/visits', [EngagementController::class, 'storeVisit'])->name('properties.visits.store');
    Route::post('/conversations', [EngagementController::class, 'startConversation'])->name('conversations.store');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');
    Route::patch('/visits/{visit}', [VisitController::class, 'update'])->name('visits.update');
    Route::post('/subscriptions', [SubscriptionController::class, 'subscribe'])->name('subscriptions.store');
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/listing-packages', [SubscriptionController::class, 'purchasePackage'])->name('listing-packages.store');
    Route::post('/hotel-bookings', [MarketplaceController::class, 'bookHotel'])->name('hotel-bookings.store');
    Route::post('/technician-bookings', [MarketplaceController::class, 'bookTechnician'])->name('technician-bookings.store');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::put('/account/preferences', [AccountController::class, 'preferences'])->name('account.preferences');
    Route::post('/account/blocks/{user}', [AccountController::class, 'block'])->name('account.blocks.store');
    Route::delete('/account/blocks/{block}', [AccountController::class, 'unblock'])->name('account.blocks.destroy');
    Route::post('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::post('/account/deletion', [AccountController::class, 'deletion'])->name('account.deletion');
    Route::post('/reviews', [TrustController::class, 'review'])->name('reviews.store');
    Route::post('/reports', [TrustController::class, 'report'])->name('reports.store');
    Route::post('/disputes', [TrustController::class, 'dispute'])->name('disputes.store');

    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
    Route::get('/properties/{property}/offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('/properties/{property}/offers', [OfferController::class, 'store'])->name('offers.store');
    Route::post('/offers/{offer}/counter', [OfferController::class, 'counter'])->name('offers.counter');
    Route::post('/offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
    Route::post('/offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');
    Route::post('/offers/{offer}/withdraw', [OfferController::class, 'withdraw'])->name('offers.withdraw');

    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->name('inspections.show');
    Route::get('/properties/{property}/inspections/create', [InspectionController::class, 'create'])->name('inspections.create');
    Route::post('/properties/{property}/inspections', [InspectionController::class, 'store'])->name('inspections.store');
    Route::post('/inspections/{inspection}/acknowledge', [InspectionController::class, 'acknowledge'])->name('inspections.acknowledge');
    Route::post('/inspections/{inspection}/cancel', [InspectionController::class, 'cancel'])->name('inspections.cancel');

    Route::post('/saved-searches', [EngagementController::class, 'storeSavedSearch'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [EngagementController::class, 'destroySavedSearch'])->name('saved-searches.destroy');

    Route::get('/institutional', [InstitutionalController::class, 'index'])->name('institutional.index');
    Route::post('/institutional/upload', [InstitutionalController::class, 'store'])->middleware('throttle:10,1')->name('institutional.upload');

    Route::get('/escrow-invest', [EscrowInvestController::class, 'index'])->name('escrow-invest.index');

    Route::get('/become-a-technician', [TechnicianApplicationController::class, 'create'])->name('technician.apply');
    Route::post('/become-a-technician', [TechnicianApplicationController::class, 'store'])->middleware('throttle:10,1')->name('technician.apply.store');

    Route::prefix('technician')->name('technician.')->middleware('technician')->group(function () {
        Route::get('profile', [TechnicianDashboardController::class, 'editProfile'])->name('profile.edit');
        Route::put('profile', [TechnicianDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('jobs', [TechnicianDashboardController::class, 'bookings'])->name('bookings.index');
        Route::get('jobs/{technicianBooking}', [TechnicianDashboardController::class, 'showBooking'])->name('bookings.show');
        Route::post('jobs/{technicianBooking}/quote', [TechnicianDashboardController::class, 'storeQuote'])->name('bookings.quote.store');
        Route::patch('jobs/{technicianBooking}/status', [TechnicianDashboardController::class, 'updateBookingStatus'])->name('bookings.status');
        Route::get('maintenance-requests', [TechnicianDashboardController::class, 'maintenanceRequests'])->name('maintenance-requests.index');
    });

    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('listings', [OwnerListingController::class, 'index'])->name('listings.index');
        Route::get('listings/create', [OwnerListingController::class, 'create'])->name('listings.create');
        Route::post('listings', [OwnerListingController::class, 'store'])->name('listings.store');
        Route::get('listings/{listing}/edit', [OwnerListingController::class, 'edit'])->name('listings.edit');
        Route::put('listings/{listing}', [OwnerListingController::class, 'update'])->name('listings.update');
        Route::post('listings/{listing}/media', [OwnerListingController::class, 'uploadMedia'])->name('listings.media.store');
        Route::patch('listings/{listing}/status', [OwnerListingController::class, 'updateStatus'])->name('listings.status');
        Route::post('listings/{listing}/freshness', [OwnerListingController::class, 'confirmFreshness'])->name('listings.freshness');

        Route::get('leads', [OwnerLeadsController::class, 'index'])->name('leads.index');

        Route::prefix('agency')->name('agency.')->group(function () {
            Route::get('/', [AgencyTeamController::class, 'index'])->name('index');
            Route::post('/', [AgencyTeamController::class, 'storeAgency'])->name('store');
            Route::post('agents', [AgencyTeamController::class, 'store'])->name('agents.store');
            Route::put('agents/{agent}', [AgencyTeamController::class, 'update'])->name('agents.update');
            Route::delete('agents/{agent}', [AgencyTeamController::class, 'destroy'])->name('agents.destroy');
            Route::post('listings/import', [AgencyTeamController::class, 'importListings'])->name('listings.import');
        });

        Route::prefix('rent-management')->name('rent-management.')->group(function () {
            Route::get('/', [RentManagementController::class, 'index'])->name('index');

            Route::get('tenancies/create', [WebsiteTenancyController::class, 'create'])->name('tenancies.create');
            Route::post('tenancies', [WebsiteTenancyController::class, 'store'])->name('tenancies.store');
            Route::get('tenancies/{tenancy}', [WebsiteTenancyController::class, 'show'])->name('tenancies.show');
            Route::get('tenancies/{tenancy}/edit', [WebsiteTenancyController::class, 'edit'])->name('tenancies.edit');
            Route::put('tenancies/{tenancy}', [WebsiteTenancyController::class, 'update'])->name('tenancies.update');
            Route::delete('tenancies/{tenancy}', [WebsiteTenancyController::class, 'destroy'])->name('tenancies.destroy');

            Route::post('tenancies/{tenancy}/rent-payments', [WebsiteRentPaymentController::class, 'store'])->name('tenancies.rent-payments.store');
            Route::post('rent-payments/{rentPayment}/mark-paid', [WebsiteRentPaymentController::class, 'markPaid'])->name('rent-payments.mark-paid');

            Route::get('listings/{listing}/units', [WebsiteUnitController::class, 'index'])->name('units.index');
            Route::post('listings/{listing}/units', [WebsiteUnitController::class, 'store'])->name('units.store');
            Route::put('units/{unit}', [WebsiteUnitController::class, 'update'])->name('units.update');
            Route::delete('units/{unit}', [WebsiteUnitController::class, 'destroy'])->name('units.destroy');

            Route::get('ledger', [WebsiteLedgerController::class, 'index'])->name('ledger.index');
            Route::post('ledger', [WebsiteLedgerController::class, 'store'])->name('ledger.store');

            Route::get('agreements', [WebsiteAgreementController::class, 'index'])->name('agreements.index');
            Route::post('agreements/templates', [WebsiteAgreementController::class, 'storeTemplate'])->name('agreements.templates.store');
            Route::delete('agreements/templates/{agreementTemplate}', [WebsiteAgreementController::class, 'destroyTemplate'])->name('agreements.templates.destroy');
            Route::post('agreements/generate', [WebsiteAgreementController::class, 'generate'])->name('agreements.generate');
            Route::get('agreements/{agreement}', [WebsiteAgreementController::class, 'show'])->name('agreements.show');
        });
    });
});

// ── Installer (disabled) ──────────────────────────────────────────────────────
// The installer wizard is fully disabled: the app is provisioned at deploy time
// (env + migrate + admin + installed.lock), so these routes are not registered
// at all. Uncomment the block below to re-enable the wizard.
Route::middleware(['web', RedirectIfInstalled::class])->group(function () {
    Route::get('install', [InstallerController::class, 'redirectToInstaller'])->name('installer.redirect');

    Route::prefix('install')->name('installer.')->group(function () {
        Route::get('requirements', [InstallerController::class, 'requirements'])->name('requirements');
        Route::get('database', [InstallerController::class, 'database'])->name('database');
        Route::post('database', [InstallerController::class, 'saveDatabase'])->middleware('throttle:10,5')->name('database.store');
        Route::post('database/test', [InstallerController::class, 'checkDb'])->middleware('throttle:30,1')->name('database.test');
        Route::get('admin', [InstallerController::class, 'admin'])->name('admin');
        Route::post('admin', [InstallerController::class, 'saveAdmin'])->middleware('throttle:5,5')->name('admin.store');
        Route::get('settings', [InstallerController::class, 'settings'])->name('settings');
        Route::post('settings', [InstallerController::class, 'saveSettings'])->middleware('throttle:10,5')->name('settings.store');
        Route::get('license', [InstallerController::class, 'license'])->name('license');
        Route::post('license', [InstallerController::class, 'verifyLicense'])->middleware('throttle:5,5')->name('license.verify');
        Route::get('finish', [InstallerController::class, 'finish'])->name('finish');
    });
});
