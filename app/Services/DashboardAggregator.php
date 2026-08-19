<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\Report;
use App\Models\Review;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Zone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardAggregator
{
    public function kpis(): array
    {
        $data = Cache::remember('dashboard.kpis', 300, function () {
            $today = now()->startOfDay();
            $now = now();

            return [
                'total_users' => User::whereDoesntHave('role', fn ($q) => $q->where('name', 'super_admin'))->count(),
                'active_listings' => PropertyListing::where('status', 'active')->count(),
                'today_new_listings' => PropertyListing::where('created_at', '>=', $today)->count(),
                'pending_listings' => PropertyListing::where('status', 'pending')->count(),
                'pending_verifications' => User::where('verification_status', 'pending')->count(),
                'open_reports' => Report::where('status', 'pending')->count(),
                'total_technicians' => Technician::where('status', 'active')->count(),
                'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
                'revenue_month' => Payment::where('status', 'paid')
                    ->where('paid_at', '>=', $now->copy()->startOfMonth())
                    ->sum('amount'),
                'revenue_today' => Payment::where('status', 'paid')
                    ->where('paid_at', '>=', $today)
                    ->sum('amount'),
                'revenue_total' => Payment::where('status', 'paid')->sum('amount'),
                'transactions_month' => Payment::where('paid_at', '>=', $now->copy()->startOfMonth())->count(),
                'pending_reviews' => Review::whereNull('moderated_at')->count(),
            ];
        });

        $data['unread_notifications'] = AdminNotification::where('admin_id', auth()->id())->unread()->count();

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $data;
        }

        $perms = $this->userPermissions();

        $filtered = [];

        foreach ($data as $key => $value) {
            $allowed = match (true) {
                in_array($key, ['active_listings', 'pending_listings', 'today_new_listings']) => $perms->contains('listings.view'),
                $key === 'pending_reviews' => $perms->contains('reviews.view'),
                $key === 'total_technicians' => $perms->contains('technicians.view'),
                $key === 'active_subscriptions' => $perms->contains('plans.view'),
                str_starts_with($key, 'revenue_') || str_starts_with($key, 'transactions_') => $perms->contains('payments.view'),
                default => true,
            };

            if ($allowed) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function userPermissions(): Collection
    {
        return auth()->user()->cachedPermissions();
    }

    /**
     * Percentage change of each KPI over the last N days vs the N days before.
     *
     * @return array<string, float>
     */
    public function kpiTrends(int $days = 30): array
    {
        $trends = Cache::remember("dashboard.kpi_trends.{$days}", 600, function () use ($days) {
            $currentStart = now()->subDays($days);
            $previousStart = now()->subDays($days * 2);

            $usersCurrent = User::where('created_at', '>=', $currentStart)->count();
            $usersPrevious = User::whereBetween('created_at', [$previousStart, $currentStart])->count();

            $listingsCurrent = PropertyListing::where('created_at', '>=', $currentStart)->count();
            $listingsPrevious = PropertyListing::whereBetween('created_at', [$previousStart, $currentStart])->count();

            $todayListings = PropertyListing::where('created_at', '>=', now()->startOfDay())->count();
            $yesterdayListings = PropertyListing::whereBetween('created_at', [now()->subDay()->startOfDay(), now()->startOfDay()])->count();

            $revenueCurrent = (float) Payment::where('status', 'paid')->where('paid_at', '>=', $currentStart)->sum('amount');
            $revenuePrevious = (float) Payment::where('status', 'paid')->whereBetween('paid_at', [$previousStart, $currentStart])->sum('amount');

            $reviewsCurrent = Review::whereNull('moderated_at')->where('created_at', '>=', $currentStart)->count();
            $reviewsPrevious = Review::whereNull('moderated_at')->whereBetween('created_at', [$previousStart, $currentStart])->count();

            return [
                'total_users' => $this->percentChange($usersCurrent, $usersPrevious),
                'active_listings' => $this->percentChange($listingsCurrent, $listingsPrevious),
                'today_new_listings' => $this->percentChange($todayListings, $yesterdayListings),
                'revenue_month' => $this->percentChange($revenueCurrent, $revenuePrevious),
                'pending_reviews' => $this->percentChange($reviewsCurrent, $reviewsPrevious),
            ];
        });

        $notificationsCurrent = AdminNotification::where('admin_id', auth()->id())->where('created_at', '>=', now()->subDays($days))->count();
        $notificationsPrevious = AdminNotification::where('admin_id', auth()->id())->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])->count();

        $trends['unread_notifications'] = $this->percentChange($notificationsCurrent, $notificationsPrevious);

        return $trends;
    }

    private function percentChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function userGrowth(int $days = 30): array
    {
        return Cache::remember("dashboard.user_growth.{$days}", 600, function () use ($days) {
            $results = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            return $this->fillDateGaps($results, $days);
        });
    }

    public function revenueOverTime(int $days = 30): array
    {
        return Cache::remember("dashboard.revenue.{$days}", 600, function () use ($days) {
            $results = Payment::where('status', 'paid')
                ->where('paid_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();

            return $this->fillDateGaps($results, $days);
        });
    }

    public function listingsByZone(): array
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && ! $user->hasPermission('listings.view')) {
            return [];
        }

        return Cache::remember('dashboard.listings_by_zone.v2', 600, function () {
            $zones = Zone::whereHas('listings')
                ->withCount('listings')
                ->orderByDesc('listings_count')
                ->get(['id', 'name']);

            $total = max(1, $zones->sum('listings_count'));

            $rows = $zones->take(7)->map(fn (Zone $zone) => [
                'name' => $zone->name,
                'count' => $zone->listings_count,
                'percent' => (int) round($zone->listings_count / $total * 100),
            ])->values()->all();

            $otherCount = $zones->slice(7)->sum('listings_count');

            if ($otherCount > 0) {
                $rows[] = [
                    'name' => 'Other',
                    'count' => $otherCount,
                    'percent' => (int) round($otherCount / $total * 100),
                ];
            }

            return $rows;
        });
    }

    /**
     * Daily totals of all bookings vs successful ones (hotel + technician).
     *
     * @return array<string, array{total: int, successful: int}>
     */
    public function bookingTrend(int $days = 30): array
    {
        return Cache::remember("dashboard.booking_trend.v2.{$days}", 600, function () use ($days) {
            $successfulHotel = ['confirmed', 'checked_in', 'checked_out'];
            $successfulTech = ['accepted', 'in_progress', 'completed'];

            $hotel = HotelBooking::where('created_at', '>=', now()->subDays($days))
                ->selectRaw("DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status IN ('".implode("','", $successfulHotel)."') THEN 1 ELSE 0 END) as successful")
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $tech = TechnicianBooking::where('created_at', '>=', now()->subDays($days))
                ->selectRaw("DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status IN ('".implode("','", $successfulTech)."') THEN 1 ELSE 0 END) as successful")
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $merged = [];
            $allDates = array_unique(array_merge($hotel->keys()->all(), $tech->keys()->all()));
            sort($allDates);

            foreach ($allDates as $date) {
                $merged[$date] = [
                    'total' => (int) ($hotel[$date]->total ?? 0) + (int) ($tech[$date]->total ?? 0),
                    'successful' => (int) ($hotel[$date]->successful ?? 0) + (int) ($tech[$date]->successful ?? 0),
                ];
            }

            return $this->fillDateGapsNested($merged, $days, ['total' => 0, 'successful' => 0]);
        });
    }

    public function recentActivity(int $limit = 10): array
    {
        return AuditLog::with('admin:id,name,email')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function recentListings(int $limit = 5): array
    {
        return PropertyListing::with([
            'owner:id,name,email',
            'zone:id,name',
            'coverImage',
            'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
        ])
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Merged feed of the latest platform events (registrations, listings, bookings, payments).
     *
     * @return array<int, array{type: string, title: string, description: string, created_at: Carbon}>
     */
    public function recentPlatformActivity(int $limit = 6): array
    {
        $users = User::with('role:id,name,display_name')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => [
                'type' => 'user',
                'title' => 'New user registered',
                'description' => $user->name.' joined as '.strtolower($user->role->display_name ?? 'a user'),
                'created_at' => $user->created_at,
            ]);

        $listings = PropertyListing::with('owner:id,name')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PropertyListing $listing) => [
                'type' => 'listing',
                'title' => 'New listing submitted',
                'description' => '"'.$listing->title.'" submitted by '.($listing->owner->name ?? 'Unknown'),
                'created_at' => $listing->created_at,
            ]);

        $bookings = HotelBooking::with('listing:id,title')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (HotelBooking $booking) => [
                'type' => 'booking',
                'title' => 'New booking received',
                'description' => 'Booking #BKG'.$booking->id.' received for "'.($booking->listing->title ?? 'Listing').'"',
                'created_at' => $booking->created_at,
            ]);

        $payments = Payment::with('user:id,name')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment) => [
                'type' => 'payment',
                'title' => 'Payment received',
                'description' => 'Payment of '.money($payment->amount, $payment->currency).' received from '.($payment->user->name ?? 'Unknown'),
                'created_at' => $payment->paid_at ?? $payment->created_at,
            ]);

        return $users->concat($listings)->concat($bookings)->concat($payments)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    public function paymentChart(int $days = 30): array
    {
        return Cache::remember("dashboard.payment_chart.{$days}", 600, function () use ($days) {
            $results = Payment::where('paid_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(paid_at) as date, gateway, SUM(amount) as total')
                ->where('status', 'paid')
                ->groupBy('date', 'gateway')
                ->orderBy('date')
                ->get();

            $grouped = [];
            foreach ($results as $row) {
                $date = $row->date;
                $gateway = $row->gateway;
                if (! isset($grouped[$date])) {
                    $grouped[$date] = [];
                }
                if (! isset($grouped[$date][$gateway])) {
                    $grouped[$date][$gateway] = 0;
                }
                $grouped[$date][$gateway] += (int) $row->total;
            }

            return $this->fillDateGapsRaw($grouped, $days);
        });
    }

    public function flush(): void
    {
        Cache::forget('dashboard.kpis');
        Cache::forget('dashboard.listings_by_zone.v2');

        foreach ([7, 30, 90] as $days) {
            Cache::forget("dashboard.kpi_trends.{$days}");
            Cache::forget("dashboard.user_growth.{$days}");
            Cache::forget("dashboard.revenue.{$days}");
            Cache::forget("dashboard.booking_trend.v2.{$days}");
            Cache::forget("dashboard.payment_chart.{$days}");
        }
    }

    private function fillDateGaps(array $data, int $days): array
    {
        $filled = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $filled[$date] = (int) ($data[$date] ?? 0);
        }

        return $filled;
    }

    private function fillDateGapsNested(array $data, int $days, array $default): array
    {
        $filled = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $filled[$date] = $data[$date] ?? $default;
        }

        return $filled;
    }

    private function fillDateGapsRaw(array $data, int $days): array
    {
        $filled = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $filled[$date] = $data[$date] ?? [];
        }

        return $filled;
    }
}
