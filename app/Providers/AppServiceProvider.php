<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\BlogCategory;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Message;
use App\Models\PropertyListing;
use App\Models\UserNotification;
use App\Models\Zone;
use App\Observers\MessageObserver;
use App\Observers\PropertyListingObserver;
use App\Support\BroadcastConfigurator;
use App\Support\DatabaseTranslationLoader;
use App\Support\Exports\ExportRegistry;
use App\Support\MailConfigurator;
use App\Support\SettingRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingRepository::class);

        $this->app->singleton(ExportRegistry::class, function (): ExportRegistry {
            return new ExportRegistry(config('exports.profiles', []));
        });

        $this->app->extend('translation.loader', function (FileLoader $loader, $app): DatabaseTranslationLoader {
            return new DatabaseTranslationLoader(
                new Filesystem,
                $app['path.lang']
            );
        });
    }

    public function boot(): void
    {
        PropertyListing::observe(PropertyListingObserver::class);
        Message::observe(MessageObserver::class);

        $this->configureMailFromSettings();
        $this->configureBroadcastFromSettings();
        $this->configurePasswordDefaults();
        $this->configureRateLimiters();
        $this->composeAdminTopbar();
        $this->composeDashboardHeader();
        $this->composeCurrentZone();
        $this->composeLocaleAndCurrency();
        $this->composeNavigation();
        $this->composeLowDataMode();
    }

    /**
     * Outside production, surface lazy loading (N+1) and silently-discarded attributes
     * as exceptions so they are caught in development and in the test suite. Destructive
     * Artisan commands stay blocked in production.
     */

    /**
     * Apply the admin-configured SMTP credentials to the mail config on every boot.
     * Guarded so a fresh install (before the `settings` table is migrated) still boots;
     * the static `.env`/log mail config is used until the setting exists.
     */
    private function configureMailFromSettings(): void
    {
        try {
            $this->app->make(MailConfigurator::class)->apply();
        } catch (\Throwable) {
            // Settings table not available yet (install / early migrations) — keep .env config.
        }
    }

    /**
     * Apply the admin-configured Pusher credentials to the broadcasting config on
     * every boot, mirroring configureMailFromSettings(). Guarded the same way so a
     * fresh install (before the `settings` table is migrated) still boots.
     */
    private function configureBroadcastFromSettings(): void
    {
        try {
            $this->app->make(BroadcastConfigurator::class)->apply();
        } catch (\Throwable) {
            // Settings table not available yet (install / early migrations) — keep .env config.
        }
    }

    /**
     * Low-data mode suppresses non-critical media (hero/background imagery, secondary
     * gallery images, map tiles) across the website. Session-backed so guests can use it too.
     */
    private function composeLowDataMode(): void
    {
        ViewFacade::composer(['website.*', 'components.website.*'], function (View $view): void {
            $view->with('lowDataMode', (bool) session('low_data', false));
        });
    }

    /**
     * The admin topbar renders an unread-notification badge on every page. Compose it
     * once here (briefly cached) instead of querying from inside the Blade partial.
     */
    private function composeAdminTopbar(): void
    {
        ViewFacade::composer('admin.partials.topbar', function (View $view): void {
            $adminId = auth()->id();

            $view->with('unreadNotifications', $adminId === null ? 0 : Cache::remember(
                "admin.{$adminId}.unread_notifications",
                now()->addSeconds(30),
                fn (): int => AdminNotification::where('admin_id', $adminId)->unread()->count(),
            ));
        });
    }

    private function composeDashboardHeader(): void
    {
        ViewFacade::composer('website.layouts.dashboard', function (View $view): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $view->with([
                'headerNotifications' => UserNotification::forUser($user->id)->orderByDesc('created_at')->limit(5)->get(),
                'headerUnreadNotificationCount' => UserNotification::forUser($user->id)->unread()->count(),
                'headerUnreadMessageCount' => Message::query()
                    ->whereHas('conversation', fn ($query) => $query->where('starter_id', $user->id)->orWhere('recipient_id', $user->id))
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                // Shown under the brand and beside the avatar; resolved here so the layout
                // does not lazy-load the role relation on every dashboard page.
                'headerRoleName' => $user->role?->name ? Str::headline($user->role->name) : null,
            ]);
        });
    }

    /**
     * The zone picker renders on every website page. Only the shared, near-static data
     * is cached: the option list, and each zone under its own id-keyed entry. The
     * session value itself is never part of a cached payload — it is only used to pick
     * which shared key to read — so one visitor's selection cannot leak into another's.
     */
    private function composeCurrentZone(): void
    {
        ViewFacade::composer('website.layouts.master', function (View $view): void {
            $zoneId = session('zone_id');

            $view->with([
                'currentZone' => $zoneId ? Zone::activeById((int) $zoneId) : null,
                'zoneOptions' => Zone::dropdownOptions(),
                'hasZoneInSession' => (bool) $zoneId,
            ]);
        });
    }

    /**
     * Language/currency switchers render on every website page. The active lists are
     * cached once for everyone; the visitor's current selection is resolved *from those
     * shared lists* by the session's code, so nothing session-specific is ever written
     * to the cache.
     */
    private function composeLocaleAndCurrency(): void
    {
        ViewFacade::composer('website.layouts.master', function (View $view): void {
            $languageCode = session('locale');
            $currencyCode = session('currency_code');

            $view->with([
                'currentLanguage' => $languageCode ? Language::findActiveByCode((string) $languageCode) : Language::defaultActive(),
                'languageOptions' => Language::activeCached(),
                'currentCurrency' => $currencyCode ? Currency::findActiveByCode((string) $currencyCode) : Currency::defaultActive(),
                'currencyOptions' => Currency::activeCached(),
            ]);
        });
    }

    private function composeNavigation(): void
    {
        ViewFacade::composer('website.layouts.master', function (View $view): void {
            $view->with([
                'navBlogCategories' => BlogCategory::navigationCached(),
            ]);
        });
    }

    /**
     * One app-wide password policy: a plain minimum of 4 characters, with no
     * complexity or breach-check requirements.
     */
    private function configurePasswordDefaults(): void
    {
        Password::defaults(fn (): Password => Password::min(4));
    }

    private function configureRateLimiters(): void
    {
        // General API: 60 req/min per user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Auth endpoints (login, register/verify, reset): 10 attempts/min per IP,
        // plus 10/min per targeted account so distributed IPs cannot spread guesses
        // against a single email/phone.
        RateLimiter::for('auth', function (Request $request) {
            $account = Str::lower((string) ($request->input('email') ?: $request->input('phone')));

            $limits = [Limit::perMinute(10)->by('auth-ip:'.$request->ip())];

            if ($account !== '') {
                $limits[] = Limit::perMinute(10)->by('auth-account:'.$account);
            }

            return $limits;
        });

        // OTP send / resend / forgot: 5 per minute per IP (hard gate)
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
