# Distrax — Code Structure

A **multi-tenant real estate marketplace** built with Laravel 13, PHP 8.3+, and Sanctum for API authentication.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Vite, Blade templates |
| Real-time | Pusher |
| Payments | Stripe, Razorpay, Paystack, PayPal, Bkash |
| Auth | Sanctum (API), Session (Web) |
| API Docs | Scribe |
| PDF | DomPDF |
| Excel | Maatwebsite Excel |
| Testing | Pest |
| Backups | Spatie Backup |

---

## Directory Overview

```
distrax/
├── app/
│   ├── Console/Commands/       # Artisan commands (cron, seeding, updates)
│   ├── Helpers/                # Global helper functions (settings, branding, pricing)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin panel (~40 controllers)
│   │   │   ├── Api/V1/         # RESTful API endpoints
│   │   │   ├── Website/        # Public web controllers
│   │   │   └── Installer/      # Setup wizard (disabled at deploy)
│   │   ├── Middleware/         # Auth, admin, locale, etc.
│   │   └── Requests/          # Form validation requests
│   ├── Models/                 # 70+ Eloquent models
│   ├── Providers/              # Service providers
│   ├── Services/               # Business logic (~50 services)
│   │   └── Payment/            # Gateway drivers (Stripe, Razorpay, etc.)
│   ├── Support/                # Repository & value objects
│   └── Traits/                 # Reusable traits (ApiResponse, HasPermissions)
├── bootstrap/                  # App bootstrap & providers
├── config/                     # Configuration files
├── database/
│   ├── data/                   # Seed data (countries.json)
│   ├── factories/              # Model factories
│   ├── migrations/             # 90+ migration files
│   └── seeders/                # Database seeders
├── lang/                       # Translation files (en.json, bn.json)
├── public/                     # Public assets, API docs, favicon
├── resources/
│   └── views/
│       ├── components/         # Blade components (admin, forms, modals)
│       ├── emails/             # Email templates
│       ├── layouts/            # Layout files
│       └── pages/              # Page-specific views
├── routes/
│   ├── admin.php               # Admin panel routes
│   ├── api.php                 # REST API (v1)
│   ├── web.php                 # Public website routes
│   ├── channels.php            # Broadcast channels
│   └── console.php             # Console command schedules
├── storage/                    # Logs, cache, debugbar
├── tests/                      # Pest test suites
├── composer.json               # PHP dependencies
├── package.json                # JS dependencies
└── vite.config.js              # Vite bundler config
```

---

## Architecture Layers

### 1. Controllers

Controllers are organized into three distinct layers:

#### Admin Panel (`app/Http/Controllers/Admin/`)

The admin panel handles all backend management operations. Protected by `auth`, `admin`, and `AdminAuditMiddleware`.

| Controller | Purpose |
|-----------|---------|
| `DashboardController` | Admin dashboard, global search |
| `UserController` | User CRUD, account deletion requests |
| `RoleController` | Role management |
| `ListingController` | Property listing management, approve/reject/archive |
| `ZoneController` | Geographic zones CRUD, import/export |
| `AgencyController` | Real estate agencies, agent management |
| `TechnicianController` | Technician CRUD, approval, zone assignment |
| `TechnicianBookingController` | Technician booking management |
| `MaintenanceRequestController` | Maintenance request handling |
| `HotelBookingController` | Hotel booking management |
| `SubscriptionPlanController` | Subscription plan management |
| `ListingPackageController` | Listing package management |
| `PlanFeatureController` | Plan feature management |
| `UserSubscriptionController` | User subscription management |
| `CouponController` | Coupon CRUD |
| `WalletController` | User wallet management |
| `PaymentController` | Payment listings, refunds |
| `PayoutController` | Payout processing |
| `InvoiceController` | Invoice generation |
| `ReviewModerationController` | Review moderation |
| `ModerationReportController` | Report moderation |
| `DisputeController` | Dispute resolution |
| `BlogController` | Blog posts and categories |
| `CmsPageController` | CMS page management |
| `FaqController` | FAQ management |
| `SeoMetaController` | SEO metadata |
| `AreaLandingPageController` | Area landing pages |
| `BannerController` | Banner/ad management |
| `TestimonialController` | Testimonial management |
| `ProjectController` | Project management |
| `CampaignController` | Marketing campaigns |
| `ReferralRuleController` | Referral rules |
| `EmailTemplateController` | Email template management |
| `SettingController` | System settings |
| `LanguageController` | Language management |
| `TranslationController` | Translation management |
| `CurrencyController` | Currency management |
| `ExportController` | CSV/PDF exports |
| `AuditLogController` | Audit log viewer |
| `AdminNotificationController` | Admin notifications |

#### Public Website (`app/Http/Controllers/Website/`)

Public-facing controllers for the website frontend.

| Controller | Purpose |
|-----------|---------|
| `HomeController` | Homepage |
| `PropertyController` | Property listings |
| `MarketplaceController` | Technicians, agencies, agents |
| `ProjectController` | Projects |
| `BlogController` | Blog |
| `ContactController` | Contact form |
| `PageController` | Static pages, FAQ |
| `ZoneController` | Zone-based landing pages |
| `DashboardController` | User dashboard |
| `AccountController` | Profile, settings, password |
| `EngagementController` | Favorites, compare, saved searches, contact reveal |
| `MessageController` | Conversations, messages |
| `NotificationController` | User notifications |
| `VisitController` | Visit scheduling |
| `SubscriptionController` | Wallet, subscriptions, packages |
| `CheckoutController` | Payment checkout |
| `MaintenanceRequestController` | User maintenance requests |
| `OwnerListingController` | Owner listing management |
| `OwnerLeadsController` | Owner lead analytics |
| `AgencyTeamController` | Agency/agent team management |
| `TechnicianApplicationController` | Technician application |
| `TechnicianDashboardController` | Technician self-service dashboard |
| `PreferenceController` | Locale, currency, low-data mode |
| `RentManagement/` | Tenancies, rent payments, units, ledger, agreements |

#### REST API (`app/Http/Controllers/Api/V1/`)

Versioned API under `/api/v1/`. Uses Sanctum for authentication.

| Controller | Purpose |
|-----------|---------|
| `Auth/AuthController` | Register, login, social auth, OTP, password reset |
| `Config/BootstrapController` | App config, translations, property types, amenities |
| `Zone/ZoneController` | Zone listing, countries, resolve |
| `Listing/ListingController` | Listing CRUD, media, share, translate |
| `Listing/ContactRevealController` | Contact reveal |
| `Listing/OwnerLeadsController` | Owner leads |
| `Technician/TechnicianController` | Technician CRUD, suggest, apply |
| `Technician/TechnicianBookingController` | Technician booking, quotes |
| `Technician/MaintenanceController` | Maintenance requests |
| `Booking/BookingController` | Booking CRUD |
| `Booking/AvailabilityController` | Availability calendar |
| `Engagement/FavoriteController` | Favorites |
| `Engagement/CompareController` | Compare |
| `Engagement/SavedSearchController` | Saved searches |
| `Chat/ConversationController` | Conversations, messages |
| `Communication/BlockController` | Block/unblock users |
| `Communication/ReportController` | Report users/content |
| `Visit/VisitController` | Visit scheduling |
| `Subscription/PlanController` | Subscription plans |
| `Subscription/SubscriptionController` | Subscription management |
| `Subscription/LimitsController` | Subscription limits |
| `Package/PackageController` | Listing packages |
| `Payment/PaymentController` | Payment initiation, refund |
| `Payment/WebhookController` | Payment gateway webhooks |
| `Wallet/WalletController` | Wallet, transactions, topup |
| `Coupon/CouponController` | Coupon apply |
| `Profile/ProfileController` | Profile CRUD, verification, export, deletion |
| `Profile/NotificationPreferenceController` | Notification preferences |
| `Notification/NotificationController` | Notifications |
| `Notification/DeviceController` | Push token registration |
| `Review/ReviewController` | Reviews |
| `Blog/BlogController` | Blog |
| `Content/ContentController` | Pages, FAQ, contact |
| `RentManagement/` | Units, tenancies, rent payments, ledger, agreements |

---

### 2. Models (`app/Models/`)

70+ Eloquent models organized by domain:

#### Core Models

| Model | Description |
|-------|-------------|
| `User` | User accounts |
| `Role` | User roles |
| `Permission` | Granular permissions |
| `Setting` | System settings |
| `Language` | Supported languages |
| `Currency` | Supported currencies |
| `Translation` | Translations |
| `Country` | Countries |
| `Zone` | Geographic zones with polygons |

#### Property Models

| Model | Description |
|-------|-------------|
| `PropertyListing` | Property listings |
| `PropertyImage` | Listing images |
| `PropertyVideo` | Listing videos |
| `CustomField` | Custom field definitions |
| `CustomFieldValue` | Custom field values |
| `AvailabilityCalendar` | Booking availability |
| `SeoMeta` | SEO metadata |
| `AreaLandingPage` | Area landing pages |

#### Technician Models

| Model | Description |
|-------|-------------|
| `Technician` | Technician profiles |
| `TechnicianCategory` | Technician categories |
| `TechnicianBooking` | Technician bookings |
| `Quote` | Booking quotes |
| `MaintenanceRequest` | Maintenance requests |

#### Rent Management Models

| Model | Description |
|-------|-------------|
| `Unit` | Rental units |
| `Tenancy` | Tenancy agreements |
| `RentPayment` | Rent payments |
| `RentReceipt` | Rent receipts |
| `LedgerTransaction` | Ledger transactions |
| `AgreementTemplate` | Agreement templates |
| `Agreement` | Generated agreements |

#### Finance Models

| Model | Description |
|-------|-------------|
| `SubscriptionPlan` | Subscription plans |
| `PlanFeature` | Plan features |
| `UserSubscription` | User subscriptions |
| `ListingPackage` | Listing packages |
| `Wallet` | User wallets |
| `WalletTransaction` | Wallet transactions |
| `Payment` | Payments |
| `Refund` | Refunds |
| `Payout` | Payouts |
| `Invoice` | Invoices |
| `Activation` | Payment activations |
| `Coupon` | Coupons |
| `IdempotencyKey` | Idempotency for payments |

#### Engagement Models

| Model | Description |
|-------|-------------|
| `Favorite` | User favorites |
| `Compare` | Property comparisons |
| `SavedSearch` | Saved searches |
| `ContactReveal` | Contact reveal logs |
| `VisitSchedule` | Visit scheduling |
| `Conversation` | Chat conversations |
| `Message` | Chat messages |
| `MessageAttachment` | Message attachments |

#### Trust & Moderation Models

| Model | Description |
|-------|-------------|
| `Review` | User reviews |
| `Report` | Content reports |
| `Dispute` | Disputes |
| `Block` | User blocks |

#### CMS Models

| Model | Description |
|-------|-------------|
| `Blog` | Blog posts |
| `BlogCategory` | Blog categories |
| `CmsPage` | CMS pages |
| `Faq` | FAQ items |
| `Banner` | Banners/ad slots |
| `Testimonial` | Testimonials |
| `PageFeedback` | Page feedback |
| `EmailTemplate` | Email templates |

#### Admin & System Models

| Model | Description |
|-------|-------------|
| `AdminNotification` | Admin notifications |
| `UserNotification` | User notifications |
| `NotificationPreference` | Notification preferences |
| `AuditLog` | Audit logs |
| `Export` | Export jobs |
| `Otp` | OTP codes |
| `Device` | Push devices |
| `ContactMessage` | Contact form messages |
| `NewsletterSubscriber` | Newsletter subscribers |
| `Campaign` | Marketing campaigns |
| `ReferralRule` | Referral rules |

---

### 3. Services (`app/Services/`)

Business logic layer, kept separate from controllers.

#### Payment Services

| Service | Purpose |
|---------|---------|
| `Payment/GatewayFactory` | Resolves payment gateway by driver name |
| `Payment/PaymentActivationService` | Activates pending payments |
| `Payment/CheckoutUrlResolver` | Resolves checkout URLs |
| `Payment/RefundService` | Handles refunds |
| `Payment/Drivers/StripeGateway` | Stripe driver |
| `Payment/Drivers/RazorpayGateway` | Razorpay driver |
| `Payment/Drivers/PaystackGateway` | Paystack driver |
| `Payment/Drivers/PayPalGateway` | PayPal driver |
| `Payment/Drivers/BkashGateway` | Bkash driver |

#### Core Business Services

| Service | Purpose |
|---------|---------|
| `CurrencyConverter` | Multi-currency conversion |
| `DistanceService` | Haversine distance calculations |
| `PointInPolygon` | Polygon containment for zone matching |
| `AvailabilityService` | Booking availability logic |
| `AgreementGenerator` | PDF agreement generation |
| `OwnerLeadsService` | Owner lead tracking |
| `TechnicianSuggestionService` | Technician recommendation |
| `SavedSearchMatcherService` | Saved search matching |
| `ContactRevealService` | Contact reveal with lead tracking |
| `MaintenanceRequestService` | Maintenance request workflow |
| `PlanPurchaseService` | Plan purchase flow |
| `WalletService` | Wallet operations |
| `CouponService` | Coupon validation/discount |
| `CancellationService` | Cancellation flow |
| `SubscriptionRefunder` | Subscription refunds |

#### Notification & Communication

| Service | Purpose |
|---------|---------|
| `NotificationCenter` | Central notification dispatch |
| `NotificationDispatcher` | Push/email notification dispatch |
| `FcmSender` | Firebase Cloud Messaging |
| `SmsSender` | SMS delivery |
| `OtpService` | OTP generation/verification |
| `SocialAuthService` | Social login (Google, etc.) |
| `CampaignSender` | Marketing campaign sending |

#### Data & Export

| Service | Purpose |
|---------|---------|
| `ExportManager` | Export job management |
| `CsvStreamExporter` | Streaming CSV export |
| `ListingCsvExporter` | Listing CSV export |
| `AgencyCsvExporter` | Agency CSV export |
| `AgencyCsvImporter` | Agency CSV import |
| `ZoneCsvExporter` | Zone CSV export |
| `ZoneCsvImporter` | Zone CSV import |
| `PaymentCsvExporter` | Payment CSV export |
| `PropertyListingCsvImporter` | Listing CSV import |

#### Admin & UI

| Service | Purpose |
|---------|---------|
| `AdminMenu` | Admin sidebar menu builder |
| `AdminIcons` | Admin icon definitions |
| `AdminGlobalSearch` | Global search across models |
| `DashboardAggregator` | Dashboard stats aggregation |
| `HomePageService` | Homepage data |
| `SchemaMarkupService` | Structured data markup |
| `TranslationService` | Translation management |

#### Utility

| Service | Purpose |
|---------|---------|
| `AuditLogger` | Audit log recording |
| `IdempotencyService` | Idempotency key management |
| `UniqueSlug` | Slug generation with uniqueness |
| `SitemapGenerator` | Sitemap XML generation |
| `PostEntitlementService` | Post-purchase entitlements |
| `VerifiedReviewGuard` | Verified purchase review guard |
| `DisputeResolver` | Dispute resolution logic |

---

### 4. Routes

#### Web Routes (`routes/web.php`)

| Path | Description |
|------|-------------|
| `/` | Homepage |
| `/properties` | Property listings index |
| `/properties/{slug}` | Property detail (slug-based) |
| `/technicians`, `/agencies`, `/agents` | Marketplace listings |
| `/projects` | Projects |
| `/areas/{zone}` | Zone landing pages |
| `/blog` | Blog |
| `/contact` | Contact form |
| `/login`, `/register` | Authentication |
| `/dashboard` | User dashboard |
| `/owner/*` | Owner management (listings, rent management) |
| `/technician/*` | Technician self-service |
| `/install/*` | Setup wizard (disabled) |

#### API Routes (`routes/api.php`)

All under `/api/v1/` with `SetLocaleAndCurrency` middleware.

**Public (no auth):**
- `/config/bootstrap`, `/translations/{locale}`, `/custom-fields`, `/property-types`, `/amenities`
- `/faqs`, `/contact`, `/pages/{slug}`
- `/blog`
- `/auth/*` (register, login, social, forgot/reset password)
- `/zones/*`
- `/listings/*` (read)
- `/technicians/*` (read)
- `/plans`, `/packages`, `/reviews` (read), `/compare`

**Authenticated (Sanctum):**
- `/me` — Profile, password, verification, notification preferences, export, deletion
- `/listings/*` — CRUD, media, status
- `/favorites`, `/saved-searches`, `/compare`
- `/conversations` — Chat
- `/block`, `/reports`
- `/visits`
- `/bookings`, `/technician-bookings`
- `/maintenance-requests`
- `/rent-management/*` — Units, tenancies, rent payments, ledger, agreements
- `/subscriptions`, `/wallet`, `/coupons`
- `/reviews` (write)
- `/notifications`, `/devices`
- `/payments/*`

**Webhooks (no auth):**
- `/webhooks/{gateway}`
- `/payments/callback/{gateway}`

#### Admin Routes (`routes/admin.php`)

All under `/admin/` with `auth`, `admin`, `AdminAuditMiddleware`.

Permission-gated routes for:
- Dashboard, Users, Roles, Listings, Zones, Agencies, Technicians
- Bookings, Maintenance, Hotels
- Plans, Packages, Subscriptions, Coupons, Wallets
- Payments, Refunds, Payouts, Invoices
- Reviews, Reports, Disputes
- CMS, FAQ, Blog, SEO, Area Pages
- Banners, Testimonials, Projects
- Marketing, Campaigns, Referrals
- Settings, Languages, Translations, Currencies
- Email Templates, Audit Logs, Notifications

---

### 5. Key Patterns

#### Gateway Factory Pattern
```
Services/Payment/GatewayFactory
  ├── Drivers/StripeGateway
  ├── Drivers/RazorpayGateway
  ├── Drivers/PaystackGateway
  ├── Drivers/PayPalGateway
  └── Drivers/BkashGateway
```
Payment gateways are swappable. `GatewayFactory::make('stripe')` resolves the correct driver.

#### Service Layer Pattern
Controllers delegate to services for business logic. Example:
- `PropertyController` → `DistanceService`, `SchemaMarkupService`
- `SubscriptionController` → `PlanPurchaseService`, `WalletService`
- `BookingController` → `AvailabilityService`

#### Slug-Based Public URLs
Property, technician, agency, and agent pages use slugs. Numeric-ID legacy URLs are 301-redirected to canonical slugs.

#### Subscription Gating
Rent management features are gated by subscription tier via `GatesRentManagementFeatures` trait.

#### Idempotency
Payment operations use `IdempotencyKey` model + `IdempotencyService` to prevent duplicate charges.

#### Multi-Currency
`CurrencyConverter` service handles conversion. Zone-based currency defaults.

#### Permission System
Granular permissions (e.g., `listings.view`, `listings.create`, `payments.edit`) applied via middleware on admin routes.

---

### 6. Console Commands

| Command | Purpose |
|---------|---------|
| `SendRentPaymentReminders` | Cron: send rent payment due reminders |
| `RefreshExchangeRates` | Cron: refresh exchange rates |
| `ExpireSubscriptions` | Cron: expire subscriptions |
| `FlagStaleListings` | Cron: flag stale listings |
| `GenerateSitemap` | Generate sitemap.xml |
| `AppUpdate` | Post-update migration script |
| `DemoDataSeeder` | Seed demo data |

---

### 7. Middleware

| Middleware | Purpose |
|-----------|---------|
| `auth` | Web authentication |
| `auth:sanctum` | API authentication |
| `admin` | Admin role check |
| `technician` | Technician role check |
| `guest` | Redirect if authenticated |
| `not-blocked` | Block check |
| `permission:*` | Granular permission check |
| `AdminAuditMiddleware` | Audit logging for admin actions |
| `RedirectIfInstalled` | Redirect to installer if not installed |
| `SetLocaleAndCurrency` | Accept-Language + X-Currency headers |
| `throttle:*` | Rate limiting |

---

### 8. Testing

Tests use **Pest** framework. Test files are in `tests/` directory.

```bash
composer test    # Run all tests
php artisan test # Alternative
```
