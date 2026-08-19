<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Blog\BlogController;
use App\Http\Controllers\Api\V1\Booking\AvailabilityController;
use App\Http\Controllers\Api\V1\Booking\BookingController;
use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Communication\BlockController;
use App\Http\Controllers\Api\V1\Communication\ReportController;
use App\Http\Controllers\Api\V1\Config\BootstrapController;
use App\Http\Controllers\Api\V1\Content\ContentController;
use App\Http\Controllers\Api\V1\Coupon\CouponController;
use App\Http\Controllers\Api\V1\Engagement\CompareController;
use App\Http\Controllers\Api\V1\Engagement\FavoriteController;
use App\Http\Controllers\Api\V1\Engagement\SavedSearchController;
use App\Http\Controllers\Api\V1\Listing\ContactRevealController;
use App\Http\Controllers\Api\V1\Listing\ListingController;
use App\Http\Controllers\Api\V1\Listing\OwnerLeadsController;
use App\Http\Controllers\Api\V1\Notification\DeviceController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Package\PackageController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Payment\WebhookController;
use App\Http\Controllers\Api\V1\Profile\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\RentManagement\AgreementController;
use App\Http\Controllers\Api\V1\RentManagement\AgreementTemplateController;
use App\Http\Controllers\Api\V1\RentManagement\LedgerTransactionController;
use App\Http\Controllers\Api\V1\RentManagement\RentPaymentController;
use App\Http\Controllers\Api\V1\RentManagement\TenancyController;
use App\Http\Controllers\Api\V1\RentManagement\UnitController;
use App\Http\Controllers\Api\V1\Review\ReviewController;
use App\Http\Controllers\Api\V1\Subscription\LimitsController;
use App\Http\Controllers\Api\V1\Subscription\PlanController;
use App\Http\Controllers\Api\V1\Subscription\SubscriptionController;
use App\Http\Controllers\Api\V1\Technician\MaintenanceController;
use App\Http\Controllers\Api\V1\Technician\TechnicianBookingController;
use App\Http\Controllers\Api\V1\Technician\TechnicianController;
use App\Http\Controllers\Api\V1\Visit\VisitController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use App\Http\Controllers\Api\V1\Zone\ZoneController;
use App\Http\Middleware\SetLocaleAndCurrency;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  —  /api/v1/*
|--------------------------------------------------------------------------
|
| All routes share:
|  - api middleware group (throttle:api, Sanctum stateless auth, etc.)
|  - SetLocaleAndCurrency middleware (Accept-Language + X-Currency headers)
|
*/

Route::prefix('v1')
    ->middleware(['api', SetLocaleAndCurrency::class])
    ->group(function () {

        // ── Config / Bootstrap (public — no auth required) ───────────────────
        Route::prefix('config')->name('api.v1.config.')->group(function () {
            Route::get('bootstrap', [BootstrapController::class, 'bootstrap'])->name('bootstrap');
        });
        Route::get('translations/{locale}', [BootstrapController::class, 'translations'])
            ->name('api.v1.translations');
        Route::get('custom-fields', [BootstrapController::class, 'customFields'])
            ->name('api.v1.custom-fields');
        Route::get('property-types', [BootstrapController::class, 'propertyTypes'])
            ->name('api.v1.property-types');
        Route::get('amenities', [BootstrapController::class, 'amenities'])
            ->name('api.v1.amenities');

        // ── Content / support (public) ────────────────────────────────────────
        Route::get('faqs', [ContentController::class, 'faqs'])
            ->name('api.v1.faqs');
        Route::post('contact', [ContentController::class, 'contact'])
            ->middleware('throttle:10,1')
            ->name('api.v1.contact');
        // Keep the {slug} route last so it never shadows the static paths above.
        Route::get('pages/{slug}', [ContentController::class, 'page'])
            ->name('api.v1.pages.show');

        // ── Blog (public) ─────────────────────────────────────────────────────
        Route::prefix('blog')->name('api.v1.blog.')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            // Static segment before {slug} so it is matched first.
            Route::get('categories', [BlogController::class, 'categories'])->name('categories');
            Route::get('{slug}', [BlogController::class, 'show'])->name('show');
        });

        // ── Authentication (public) ───────────────────────────────────────────
        Route::prefix('auth')->group(function () {

            Route::prefix('register')->group(function () {

                Route::post('start', [AuthController::class, 'registerStart'])
                    ->middleware('throttle:otp')
                    ->name('api.v1.auth.register.start');

                Route::post('verify', [AuthController::class, 'registerVerify'])
                    ->middleware('throttle:auth')
                    ->name('api.v1.auth.register.verify');
            });

            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:auth')
                ->name('api.v1.auth.login');

            Route::post('social', [AuthController::class, 'social'])
                ->middleware('throttle:auth')
                ->name('api.v1.auth.social');

            Route::post('forgot', [AuthController::class, 'forgotPassword'])
                ->middleware('throttle:otp')
                ->name('api.v1.auth.forgot');

            Route::post('reset', [AuthController::class, 'resetPassword'])
                ->middleware('throttle:auth')
                ->name('api.v1.auth.reset');
        });

        // Backwards-compatible aliases for older clients.
        Route::prefix('register')->group(function () {

            Route::post('start', [AuthController::class, 'registerStart'])
                ->middleware('throttle:otp')
                ->name('api.v1.register.start');

            Route::post('verify', [AuthController::class, 'registerVerify'])
                ->middleware('throttle:auth')
                ->name('api.v1.register.verify');
        });

        // ── Zones (public — guests need to pick location) ─────────────────────
        // IMPORTANT: static segments (countries, resolve) are declared BEFORE
        // the {zone} parameter route to ensure correct matching order.
        Route::prefix('zones')->name('api.v1.zones.')->group(function () {

            Route::get('countries', [ZoneController::class, 'countries'])
                ->name('countries');

            Route::get('resolve', [ZoneController::class, 'resolve'])
                ->middleware('throttle:60,1')
                ->name('resolve');

            Route::get('/', [ZoneController::class, 'index'])
                ->name('index');

            Route::get('{zone}', [ZoneController::class, 'show'])
                ->name('show');

            Route::get('{zone}/areas', [ZoneController::class, 'areas'])
                ->name('areas');
        });

        // ── Listings (public reads) ───────────────────────────────────────────
        // IMPORTANT: static sub-paths declared BEFORE {id} to avoid capture
        Route::prefix('listings')->name('api.v1.listings.')->group(function () {

            Route::get('/', [ListingController::class, 'index'])->name('index');
            Route::get('{id}/distance', [ListingController::class, 'distance'])->name('distance');
            Route::get('{id}/share', [ListingController::class, 'share'])->name('share');
            Route::post('{id}/translate', [ListingController::class, 'translate'])->name('translate');
            Route::get('{listing}/availability', [AvailabilityController::class, 'show'])->name('availability');
            Route::get('{listing}/suggested-technicians', [TechnicianController::class, 'suggestForListing'])->name('suggested-technicians');
            Route::get('{id}', [ListingController::class, 'show'])->name('show');
        });

        // ── Technicians (public reads) ────────────────────────────────────────
        // IMPORTANT: 'suggest' declared BEFORE {technician} to avoid capture
        Route::prefix('technicians')->name('api.v1.technicians.')->group(function () {
            Route::get('suggest', [TechnicianController::class, 'suggest'])->name('suggest');
            Route::get('/', [TechnicianController::class, 'index'])->name('index');
            Route::get('{technician}', [TechnicianController::class, 'show'])->name('show');
        });

        // ── Plans (public — guests need to browse plans) ──────────────────────
        Route::prefix('plans')->name('api.v1.plans.')->group(function () {
            Route::get('/', [PlanController::class, 'index'])->name('index');
            Route::get('{plan}', [PlanController::class, 'show'])->name('show');
        });

        // ── Packages (public browse) ──────────────────────────────────────────
        Route::get('packages', [PackageController::class, 'index'])
            ->name('api.v1.packages.index');

        Route::get('technician-categories', [TechnicianController::class, 'categories'])
            ->name('api.v1.technician-categories.index');

        // ── Reviews (public read) ─────────────────────────────────────────────
        Route::get('reviews', [ReviewController::class, 'index'])
            ->name('api.v1.reviews.public.index');

        // ── Compare (public — no auth needed to compare by IDs) ──────────────
        Route::prefix('compare')->name('api.v1.compare.')->group(function () {
            Route::get('/', [CompareController::class, 'show'])->name('show');
        });

        // ── Authenticated routes ──────────────────────────────────────────────
        Route::middleware(['auth:sanctum', 'not-blocked'])->group(function () {

            // Auth
            Route::post('auth/logout', [AuthController::class, 'logout'])
                ->name('api.v1.auth.logout');

            // ── Profile (/me) ─────────────────────────────────────────────────
            Route::prefix('me')->name('api.v1.me.')->group(function () {

                Route::get('/', [ProfileController::class, 'show'])->name('show');
                Route::put('/', [ProfileController::class, 'update'])->name('update');
                Route::post('photo', [ProfileController::class, 'uploadPhoto'])->name('photo');
                Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');

                // Owner verification
                Route::post('verification', [ProfileController::class, 'submitVerification'])->name('verification.submit');
                Route::get('verification', [ProfileController::class, 'verificationStatus'])->name('verification.status');

                // Notification preferences
                Route::get('notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notifications.index');
                Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.update');

                // Privacy & data export
                Route::get('export', [ProfileController::class, 'exportData'])->name('export');
                Route::get('export/download', [ProfileController::class, 'downloadExport'])->name('export.download');

                // Account deletion
                Route::delete('/', [ProfileController::class, 'requestDeletion'])->name('delete');
            });

            // ── Listings (write operations) ───────────────────────────────────
            Route::prefix('listings')->name('api.v1.listings.')->group(function () {

                Route::post('/', [ListingController::class, 'store'])->name('store');

                Route::put('{listing}', [ListingController::class, 'update'])->name('update');
                Route::delete('{listing}', [ListingController::class, 'destroy'])->name('destroy');

                Route::post('{listing}/media', [ListingController::class, 'uploadMedia'])->name('media.upload');
                Route::delete('{listing}/media/{type}/{mediaId}', [ListingController::class, 'deleteMedia'])->name('media.delete');

                Route::patch('{listing}/status', [ListingController::class, 'updateStatus'])->name('status');
                Route::post('{listing}/freshness-confirm', [ListingController::class, 'confirmFreshness'])->name('freshness.confirm');
            });

            // ── Favorites ─────────────────────────────────────────────────────
            Route::prefix('favorites')->name('api.v1.favorites.')->group(function () {
                Route::get('/', [FavoriteController::class, 'index'])->name('index');
                Route::post('{listing}', [FavoriteController::class, 'store'])->name('store');
                Route::delete('{listing}', [FavoriteController::class, 'destroy'])->name('destroy');
            });

            // ── Saved Searches ────────────────────────────────────────────────
            Route::prefix('saved-searches')->name('api.v1.saved-searches.')->group(function () {
                Route::get('/', [SavedSearchController::class, 'index'])->name('index');
                Route::post('/', [SavedSearchController::class, 'store'])->name('store');
                Route::put('{savedSearch}', [SavedSearchController::class, 'update'])->name('update');
                Route::delete('{savedSearch}', [SavedSearchController::class, 'destroy'])->name('destroy');
            });

            // ── Compare (persisted set) ───────────────────────────────────────
            // 'my' declared before {listing} to avoid it being captured as a model ID
            Route::prefix('compare')->name('api.v1.compare.')->group(function () {
                Route::get('my', [CompareController::class, 'mySet'])->name('my');
                Route::post('{listing}', [CompareController::class, 'add'])->name('add');
                Route::delete('/', [CompareController::class, 'clear'])->name('clear');
                Route::delete('{listing}', [CompareController::class, 'remove'])->name('remove');
            });

            // ── Chat / Conversations ──────────────────────────────────────────
            // 'read' declared before {conversation} to avoid capture
            Route::prefix('conversations')->name('api.v1.conversations.')->group(function () {
                Route::get('/', [ConversationController::class, 'index'])->name('index');
                Route::post('/', [ConversationController::class, 'store'])->name('store');
                Route::get('{conversation}/messages', [ConversationController::class, 'messages'])->name('messages');
                Route::post('{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('messages.send');
                Route::post('{conversation}/read', [ConversationController::class, 'markRead'])->name('read');
            });

            // ── Contact Reveal ────────────────────────────────────────────────
            Route::post('listings/{listing}/reveal-contact', ContactRevealController::class)
                ->name('api.v1.listings.reveal-contact');

            // ── Owner Leads & Analytics ─────────────────────────────────────────
            Route::prefix('owner/leads')->name('api.v1.owner.leads.')->group(function () {
                Route::get('/', [OwnerLeadsController::class, 'index'])->name('index');
                Route::get('summary', [OwnerLeadsController::class, 'summary'])->name('summary');
            });

            // ── Visit Scheduling ──────────────────────────────────────────────
            Route::post('listings/{listing}/visits', [VisitController::class, 'store'])
                ->name('api.v1.visits.store');
            Route::prefix('visits')->name('api.v1.visits.')->group(function () {
                Route::get('/', [VisitController::class, 'index'])->name('index');
                Route::patch('{visit}', [VisitController::class, 'update'])->name('update');
            });

            // ── Block ─────────────────────────────────────────────────────────
            Route::prefix('block')->name('api.v1.block.')->group(function () {
                Route::get('/', [BlockController::class, 'index'])->name('index');
                Route::post('{user}', [BlockController::class, 'store'])->name('store');
                Route::delete('{user}', [BlockController::class, 'destroy'])->name('destroy');
            });

            // ── Reports ───────────────────────────────────────────────────────
            Route::post('reports', [ReportController::class, 'store'])
                ->name('api.v1.reports.store');

            // ── Availability (owner writes) ───────────────────────────────────
            Route::put('listings/{listing}/availability', [AvailabilityController::class, 'update'])
                ->name('api.v1.listings.availability.update');

            // ── Bookings ──────────────────────────────────────────────────────
            Route::prefix('bookings')->name('api.v1.bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'index'])->name('index');
                Route::post('/', [BookingController::class, 'store'])->name('store');
                Route::get('{booking}', [BookingController::class, 'show'])->name('show');
                Route::post('{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
            });

            // ── Technician (write / self-service) ─────────────────────────────
            Route::prefix('technician')->name('api.v1.technician.')->group(function () {
                Route::post('apply', [TechnicianController::class, 'apply'])->name('apply');
                Route::put('profile', [TechnicianController::class, 'updateProfile'])->name('profile');
            });

            // ── Technician Bookings ───────────────────────────────────────────
            // Static sub-paths (quote/accept, quote/reject) before {technicianBooking}
            Route::prefix('technician-bookings')->name('api.v1.technician-bookings.')->group(function () {
                Route::get('/', [TechnicianBookingController::class, 'index'])->name('index');
                Route::post('/', [TechnicianBookingController::class, 'store'])->name('store');
                Route::get('{technicianBooking}', [TechnicianBookingController::class, 'show'])->name('show');
                Route::post('{technicianBooking}/quote', [TechnicianBookingController::class, 'submitQuote'])->name('quote.submit');
                Route::post('{technicianBooking}/quote/accept', [TechnicianBookingController::class, 'acceptQuote'])->name('quote.accept');
                Route::post('{technicianBooking}/quote/reject', [TechnicianBookingController::class, 'rejectQuote'])->name('quote.reject');
            });

            // ── Maintenance Requests ──────────────────────────────────────────
            Route::prefix('maintenance-requests')->name('api.v1.maintenance-requests.')->group(function () {
                Route::get('/', [MaintenanceController::class, 'index'])->name('index');
                Route::post('/', [MaintenanceController::class, 'store'])->name('store');
                Route::patch('{maintenanceRequest}', [MaintenanceController::class, 'update'])->name('update');
            });

            // ── Rent Management (subscriber advanced features) ─────────────────
            Route::prefix('rent-management')->name('api.v1.rent-management.')->group(function () {
                Route::prefix('units')->name('units.')->group(function () {
                    Route::get('/', [UnitController::class, 'index'])->name('index');
                    Route::post('/', [UnitController::class, 'store'])->name('store');
                    Route::put('{unit}', [UnitController::class, 'update'])->name('update');
                    Route::delete('{unit}', [UnitController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('tenancies')->name('tenancies.')->group(function () {
                    Route::get('/', [TenancyController::class, 'index'])->name('index');
                    Route::post('/', [TenancyController::class, 'store'])->name('store');
                    Route::get('{tenancy}', [TenancyController::class, 'show'])->name('show');
                    Route::put('{tenancy}', [TenancyController::class, 'update'])->name('update');
                    Route::delete('{tenancy}', [TenancyController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('rent-payments')->name('rent-payments.')->group(function () {
                    Route::get('/', [RentPaymentController::class, 'index'])->name('index');
                    Route::post('{rentPayment}/mark-paid', [RentPaymentController::class, 'markPaid'])->name('mark-paid');
                });

                Route::prefix('ledger-transactions')->name('ledger-transactions.')->group(function () {
                    Route::get('/', [LedgerTransactionController::class, 'index'])->name('index');
                    Route::get('summary', [LedgerTransactionController::class, 'summary'])->name('summary');
                    Route::post('/', [LedgerTransactionController::class, 'store'])->name('store');
                });

                Route::prefix('agreement-templates')->name('agreement-templates.')->group(function () {
                    Route::get('/', [AgreementTemplateController::class, 'index'])->name('index');
                    Route::post('/', [AgreementTemplateController::class, 'store'])->name('store');
                    Route::put('{agreementTemplate}', [AgreementTemplateController::class, 'update'])->name('update');
                    Route::delete('{agreementTemplate}', [AgreementTemplateController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('agreements')->name('agreements.')->group(function () {
                    Route::get('/', [AgreementController::class, 'index'])->name('index');
                    Route::post('/', [AgreementController::class, 'store'])->name('store');
                    Route::get('{agreement}', [AgreementController::class, 'show'])->name('show');
                });
            });

            // ── Subscriptions ─────────────────────────────────────────────────
            Route::post('subscriptions', [SubscriptionController::class, 'store'])
                ->name('api.v1.subscriptions.store');

            // ── Me: subscription, limits ──────────────────────────────────────
            Route::prefix('me')->name('api.v1.me.')->group(function () {
                Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
                Route::post('subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
                Route::get('limits', [LimitsController::class, 'show'])->name('limits');
            });

            // ── Packages (buy) ────────────────────────────────────────────────
            Route::post('packages/{package}/buy', [PackageController::class, 'buy'])
                ->name('api.v1.packages.buy');

            // ── Wallet ────────────────────────────────────────────────────────
            // 'transactions' declared BEFORE {wallet} to avoid capture (not needed here but defensive)
            Route::prefix('wallet')->name('api.v1.wallet.')->group(function () {
                Route::get('/', [WalletController::class, 'show'])->name('show');
                Route::get('transactions', [WalletController::class, 'transactions'])->name('transactions');
                Route::post('topup', [WalletController::class, 'topup'])->name('topup');
            });

            // ── Coupons ───────────────────────────────────────────────────────
            Route::post('coupons/apply', [CouponController::class, 'apply'])
                ->name('api.v1.coupons.apply');

            // ── Reviews (write operations — auth required) ────────────────────
            Route::prefix('reviews')->name('api.v1.reviews.')->group(function () {
                Route::post('/', [ReviewController::class, 'store'])->name('store');
                Route::post('{review}/report', [ReviewController::class, 'report'])->name('report');
            });

            // ── Notifications ─────────────────────────────────────────────────
            // 'unread-count' and 'read-all' declared BEFORE {notification} to avoid capture
            Route::prefix('notifications')->name('api.v1.notifications.')->group(function () {
                Route::get('/', [NotificationController::class, 'index'])->name('index');
                Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
                Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
                Route::post('{notification}/read', [NotificationController::class, 'markRead'])->name('read');
            });

            // ── Devices (push token registration) ─────────────────────────────
            Route::prefix('devices')->name('api.v1.devices.')->group(function () {
                Route::post('/', [DeviceController::class, 'store'])->name('store');
                Route::delete('{device}', [DeviceController::class, 'destroy'])->name('destroy');
            });

            // ── Payments ─────────────────────────────────────────────────────
            Route::prefix('payments')->name('api.v1.payments.')->group(function () {
                Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
                Route::post('{payment}/initiate', [PaymentController::class, 'initiate'])->name('initiate');
                Route::post('{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
            });
        });

        // ── Webhooks & Callbacks (no auth — called by gateway server or browser) ─
        Route::post('webhooks/{gateway}', [WebhookController::class, 'handle'])
            ->name('api.v1.webhooks.handle');

        Route::get('payments/callback/{gateway}', [WebhookController::class, 'callback'])
            ->name('api.v1.payments.callback');
    });
