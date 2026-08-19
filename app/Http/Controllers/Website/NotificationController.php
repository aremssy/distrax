<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = UserNotification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('website.dashboard.notifications', compact('notifications'));
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        UserNotification::forUser($request->user()->id)->unread()->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
