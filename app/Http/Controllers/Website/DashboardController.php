<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\UserNotification;
use App\Models\VisitSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $favoritesCount = $user->favorites()->count();
        $savedSearchesCount = $user->savedSearches()->count();

        $unreadMessagesCount = Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', fn (Builder $query) => $query
                ->where('starter_id', $user->id)
                ->orWhere('recipient_id', $user->id))
            ->count();

        $upcomingVisits = VisitSchedule::query()
            ->with(['listing:id,slug,title', 'listing.coverImage'])
            ->where('user_id', $user->id)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('scheduled_at')
            ->get();

        $recentNotifications = UserNotification::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            ['icon' => 'heart', 'label' => 'Favorites', 'value' => $favoritesCount, 'route' => 'dashboard.wishlist'],
            ['icon' => 'search', 'label' => 'Saved Searches', 'value' => $savedSearchesCount, 'route' => 'dashboard.wishlist'],
            ['icon' => 'message-square', 'label' => 'Unread Messages', 'value' => $unreadMessagesCount, 'route' => 'dashboard.messages'],
            ['icon' => 'calendar-check', 'label' => 'Upcoming Visits', 'value' => $upcomingVisits->count(), 'route' => 'dashboard.activity'],
        ];

        $isInvestor = $user->is_institutional
            || in_array((string) $user->buying_for, ['investment', 'fix_flip', 'development', 'land_banking', 'commercial'], true);

        if ($isInvestor) {
            $activeOffersCount = $user->offersMade()
                ->whereIn('status', ['pending', 'countered', 'accepted'])
                ->count();

            $dealsAsBuyerCount = $user->dealsAsBuyer()->count();

            $portfolioValue = $user->dealsAsBuyer()
                ->where('stage', 'completed')
                ->sum('agreed_amount');

            $stats = array_merge([
                ['icon' => 'file-text', 'label' => 'Active Offers', 'value' => $activeOffersCount, 'route' => 'offers.index'],
                ['icon' => 'briefcase', 'label' => 'Deals as Buyer', 'value' => $dealsAsBuyerCount, 'route' => 'dashboard.activity'],
            ], $stats, [
                ['icon' => 'trending-up', 'label' => 'Portfolio Value', 'value' => $portfolioValue > 0 ? money($portfolioValue) : '—', 'route' => 'dashboard.activity'],
            ]);
        }

        return view('website.dashboard.overview', [
            'stats' => $stats,
            'upcomingVisits' => $upcomingVisits,
            'recentNotifications' => $recentNotifications,
        ]);
    }
}
