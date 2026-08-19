<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationPreferencesRequest;
use App\Models\AdminNotification;
use App\Models\NotificationPreference;
use App\Services\AdminIcons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = AdminNotification::forAdmin(auth()->id())
            ->when($request->boolean('unread_only'), fn ($query) => $query->unread())
            ->latest()
            ->paginate(25);

        $unreadCount = AdminNotification::forAdmin(auth()->id())->unread()->count();

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Live feed for the topbar bell: unread/read counts plus the most recent
     * notifications. Polled by the bell so the badge and dropdown stay current
     * without a full page reload.
     */
    public function feed(Request $request): JsonResponse
    {
        $adminId = auth()->id();

        $recent = AdminNotification::forAdmin($adminId)
            ->latest()
            ->limit(10)
            ->get();

        $unread = AdminNotification::forAdmin($adminId)->unread()->count();
        $total = AdminNotification::forAdmin($adminId)->count();

        return response()->json([
            'unread_count' => $unread,
            'read_count' => $total - $unread,
            'total_count' => $total,
            'notifications' => $recent->map(function (AdminNotification $notification): array {
                $meta = AdminNotification::metaFor($notification->type);

                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'link' => $notification->link,
                    'is_read' => $notification->is_read,
                    'mark_url' => route('admin.notifications.read', $notification),
                    'time' => $notification->created_at->diffForHumans(),
                    'icon_path' => AdminIcons::PATHS[$meta['icon']] ?? AdminIcons::PATHS['bell'],
                    'icon_class' => $meta['style'],
                ];
            })->all(),
        ]);
    }

    public function markRead(Request $request, AdminNotification $notification): RedirectResponse|JsonResponse
    {
        abort_if($notification->admin_id !== auth()->id(), 403);

        $notification->update(['is_read' => true]);

        $this->flushUnreadBadge();

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => AdminNotification::forAdmin(auth()->id())->unread()->count(),
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        AdminNotification::forAdmin(auth()->id())->unread()->update(['is_read' => true]);

        $this->flushUnreadBadge();

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Drop the cached topbar badge count so it reflects the change immediately.
     */
    private function flushUnreadBadge(): void
    {
        Cache::forget('admin.'.auth()->id().'.unread_notifications');
    }

    public function preferences(): View
    {
        $events = [
            'new_user_registered',
            'listing_reported',
            'listing_pending_approval',
            'payment_received',
            'refund_requested',
            'booking_created',
            'technician_application',
            'dispute_opened',
            'review_flagged',
        ];

        $channels = ['email', 'sms', 'push', 'in_app'];

        $preferences = NotificationPreference::where('user_id', auth()->id())->get()->keyBy(fn ($p) => $p->event.'.'.$p->channel);

        return view('admin.notifications.preferences', compact('events', 'channels', 'preferences'));
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // One submit = one preference matrix. A partial save would leave the admin
        // with a mix of old and new channel settings and no way to tell which.
        DB::transaction(function () use ($data): void {
            foreach ($data['preferences'] as $pref) {
                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'event' => $pref['event'],
                        'channel' => $pref['channel'],
                    ],
                    ['enabled' => $pref['enabled']]
                );
            }
        });

        return redirect()->route('admin.notifications.preferences')->with('success', 'Preferences updated.');
    }
}
