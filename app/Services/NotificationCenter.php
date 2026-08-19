<?php

namespace App\Services;

use App\Events\AdminNotificationCreated;
use App\Events\UserNotificationCreated;
use App\Models\AdminNotification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Single entry point for creating in-app notifications.
 *
 * Business rule (product): every meaningful action notifies the admins, and when an
 * action concerns a specific website user, that user is notified too. Both paths persist
 * to their respective tables and fire a broadcast-ready event so real-time delivery can
 * be switched on later without touching call sites.
 */
class NotificationCenter
{
    /**
     * When true, all writes are suppressed. Seeders and bulk backfills wrap their work
     * in silenced() so importing demo data does not generate thousands of notifications.
     */
    private static bool $silenced = false;

    /**
     * Run a callback with notification writes suppressed, restoring the previous state
     * afterwards even if the callback throws.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function silenced(callable $callback): mixed
    {
        $previous = self::$silenced;
        self::$silenced = true;

        try {
            return $callback();
        } finally {
            self::$silenced = $previous;
        }
    }

    /**
     * Notify every admin (super/sub) about an event. The acting admin is skipped by
     * default — they already see a success toast for their own action, so telling them
     * again is noise. Pass a $permission to limit the fan-out to admins who can actually
     * see the underlying resource (e.g. only payments-capable admins hear about payments).
     */
    public function toAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        ?Model $subject = null,
        ?string $permission = null,
        ?int $exceptAdminId = null,
    ): void {
        if (self::$silenced) {
            return;
        }

        $exceptAdminId ??= auth()->id();

        $this->adminRecipients($permission, $exceptAdminId)->each(
            fn (User $admin) => $this->createAdminNotification($admin->id, $type, $title, $body, $link, $subject),
        );
    }

    /**
     * Notify one specific admin — used for personal outcomes such as "your export is ready"
     * where only the requester cares. Bypasses the mute guard so queued job results are
     * never silently dropped.
     */
    public function toAdmin(
        int $adminId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        ?Model $subject = null,
    ): void {
        $this->createAdminNotification($adminId, $type, $title, $body, $link, $subject);
    }

    private function createAdminNotification(
        int $adminId,
        string $type,
        string $title,
        ?string $body,
        ?string $link,
        ?Model $subject,
    ): void {
        $notification = new AdminNotification([
            'admin_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);

        if ($subject instanceof Model) {
            $notification->notifiable()->associate($subject);
        }

        $notification->save();

        $this->flushAdminBadge($adminId);

        event(new AdminNotificationCreated($notification));
    }

    /**
     * Notify a single website user. Used for anything tied to their account or records:
     * account changes, listing moderation outcomes, booking updates, payouts, etc.
     *
     * @param  array<string, mixed>  $data
     */
    public function toUser(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        array $data = [],
        ?Model $subject = null,
    ): void {
        if (self::$silenced) {
            return;
        }

        $notification = new UserNotification([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'is_read' => false,
        ]);

        if ($subject instanceof Model) {
            $notification->notifiable()->associate($subject);
        }

        $notification->save();

        event(new UserNotificationCreated($notification));
    }

    /**
     * Convenience for the common "an admin acted on a user's thing" shape: notify the
     * admins and, when a user is supplied, notify that user in one call.
     *
     * @param  array<string, mixed>  $userData
     */
    public function announce(
        string $type,
        string $adminTitle,
        ?User $user = null,
        ?string $userTitle = null,
        ?string $body = null,
        ?string $adminLink = null,
        ?Model $subject = null,
        ?string $permission = null,
        array $userData = [],
    ): void {
        $this->toAdmins($type, $adminTitle, $body, $adminLink, $subject, $permission);

        if ($user instanceof User) {
            $this->toUser($user, $type, $userTitle ?? $adminTitle, $body, $userData, $subject);
        }
    }

    /**
     * The admins who should receive a notification, optionally filtered to those holding
     * a given permission.
     *
     * @return Collection<int, User>
     */
    private function adminRecipients(?string $permission, ?int $exceptAdminId): Collection
    {
        $admins = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['super_admin', 'sub_admin']))
            ->when($exceptAdminId, fn ($query) => $query->whereKeyNot($exceptAdminId))
            ->get();

        if ($permission === null) {
            return $admins;
        }

        return $admins->filter(fn (User $admin): bool => $admin->hasPermission($permission))->values();
    }

    private function flushAdminBadge(int $adminId): void
    {
        Cache::forget("admin.{$adminId}.unread_notifications");
    }
}
