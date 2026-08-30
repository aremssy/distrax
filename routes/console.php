<?php

use App\Console\Commands\ExpireSubscriptions;
use App\Console\Commands\FlagExpiringVerifications;
use App\Console\Commands\FlagStaleListings;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\RefreshExchangeRates;
use App\Console\Commands\SendRentPaymentReminders;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:15')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('01:45')->withoutOverlapping();
Schedule::command(FlagStaleListings::class)->dailyAt('02:00')->withoutOverlapping();
Schedule::command(ExpireSubscriptions::class)->dailyAt('02:15')->withoutOverlapping();
Schedule::command(\App\Console\Commands\ExpireOffers::class)->everyFifteenMinutes()->withoutOverlapping();
Schedule::command(\App\Console\Commands\SendRadarDigests::class, ['daily'])->dailyAt('07:00')->withoutOverlapping();
Schedule::command(\App\Console\Commands\SendRadarDigests::class, ['weekly'])->weekly()->sundays()->at('09:00')->withoutOverlapping();
Schedule::command(GenerateSitemap::class)->dailyAt('03:00')->withoutOverlapping();
Schedule::command(SendRentPaymentReminders::class)->dailyAt('08:00')->withoutOverlapping();
Schedule::command(RefreshExchangeRates::class)->dailyAt('04:00')->withoutOverlapping();
Schedule::command(FlagExpiringVerifications::class)->dailyAt('05:00')->withoutOverlapping();

// Hard-delete accounts that passed the 30-day grace period
Schedule::call(function () {
    User::withTrashed()
        ->whereNotNull('deletion_requested_at')
        ->where('deletion_requested_at', '<=', now()->subDays(30))
        ->each(function (User $user) {
            $user->tokens()->delete();
            $user->forceDelete();
        });
})->dailyAt('03:30')->name('purge-deleted-accounts')->withoutOverlapping();
