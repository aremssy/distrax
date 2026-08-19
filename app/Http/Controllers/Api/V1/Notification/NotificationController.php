<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\UserNotificationResource;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * GET /api/v1/notifications
     *
     * Paginated in-app notifications for the authenticated user.
     * Optional filter: ?unread=1
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'unread' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $userId = $request->user()->id;
        $perPage = min((int) $request->input('per_page', 20), 50);

        $paginator = UserNotification::forUser($userId)
            ->when($request->boolean('unread'), fn ($q) => $q->unread())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginated($paginator, UserNotificationResource::class);
    }

    /**
     * GET /api/v1/notifications/unread-count
     *
     * Returns the number of unread in-app notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::forUser($request->user()->id)->unread()->count();

        return $this->success(['unread_count' => $count]);
    }

    /**
     * POST /api/v1/notifications/{notification}/read
     *
     * Mark a single notification as read.
     */
    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Notification not found.', 404);
        }

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this->success(new UserNotificationResource($notification), 'Marked as read.');
    }

    /**
     * POST /api/v1/notifications/read-all
     *
     * Mark all unread notifications for the authenticated user as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        $updated = UserNotification::forUser($request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success(['marked_read' => $updated], 'All notifications marked as read.');
    }
}
