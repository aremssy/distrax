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

        return view('website.dashboard.overview', [
            'stats' => [
                ['icon' => 'heart', 'label' => 'Favorites', 'value' => $favoritesCount, 'route' => 'dashboard.wishlist'],
                ['icon' => 'search', 'label' => 'Saved Searches', 'value' => $savedSearchesCount, 'route' => 'dashboard.wishlist'],
                ['icon' => 'message-square', 'label' => 'Unread Messages', 'value' => $unreadMessagesCount, 'route' => 'dashboard.messages'],
                ['icon' => 'calendar-check', 'label' => 'Upcoming Visits', 'value' => $upcomingVisits->count(), 'route' => 'dashboard.activity'],
            ],
            'upcomingVisits' => $upcomingVisits,
            'recentNotifications' => $recentNotifications,
        ]);
    }
}
