<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\GenericUserNotification;
use Illuminate\Database\Eloquent\Model;

class NotificationDispatcher
{
    /**
     * Maps internal notification types to notification_preferences event keys.
     * Types not in this map are delivered in-app only without a preference gate.
     */
    private const PREFERENCE_MAP = [
        'new_message' => 'chat',
        'booking_created' => 'booking',
        'booking_update' => 'booking',
        'review_posted' => 'booking',
        'lead' => 'lead',
        'saved_search_alert' => 'alert',
        'rent_reminder' => 'alert',
        'price_drop' => 'alert',
        'new_matched_deal' => 'alert',
        'disclosure_added' => 'alert',
        'offer_status_change' => 'offer',
    ];

    public function __construct(private SmsSender $sms, private FcmSender $fcm) {}

    /**
     * Dispatch a notification to a user across all active channels.
     *
     * @param  array<string, mixed>  $data  Extra context for deep-links / in-app display.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        ?Model $notifiable = null,
    ): UserNotification {
        $preferenceKey = self::PREFERENCE_MAP[$type] ?? null;
        $channels = $this->resolveChannels($user, $preferenceKey);

        // In-app is the primary channel; always written to DB.
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
            'notifiable_type' => $notifiable?->getMorphClass(),
            'notifiable_id' => $notifiable?->id,
        ]);

        if (in_array('push', $channels, true)) {
            $this->dispatchPush($user, $title, $body, $data);
        }

        if (in_array('email', $channels, true)) {
            $user->notify(new GenericUserNotification($type, $title, $body));
        }

        if (in_array('sms', $channels, true)) {
            $this->dispatchSms($user, $title, $body);
        }

        return $notification;
    }

    /**
     * Send the notification over SMS. Best-effort: a provider failure is logged by
     * SmsSender and must not break the rest of the fan-out (the in-app notification
     * has already been persisted above).
     */
    private function dispatchSms(User $user, string $title, string $body): void
    {
        if (! $user->phone || ! $this->sms->isLiveDriver()) {
            return;
        }

        $this->sms->send($user->phone, "{$title}: {$body}");
    }

    /**
     * Resolve which channels are enabled for this user + event.
     *
     * @return list<string>
     */
    private function resolveChannels(User $user, ?string $preferenceKey): array
    {
        // No preference key → always in-app only (system events like verification)
        if ($preferenceKey === null) {
            return ['in_app'];
        }

        $saved = NotificationPreference::where('user_id', $user->id)
            ->where('event', $preferenceKey)
            ->pluck('enabled', 'channel')
            ->toArray();

        $channels = [];

        foreach (['in_app', 'push', 'email', 'sms'] as $channel) {
            // Default to enabled if no preference row exists yet
            if ($saved[$channel] ?? true) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Send a Firebase Cloud Messaging push to all of the user's registered devices.
     * Best-effort — FcmSender logs failures rather than throwing.
     *
     * @param  array<string, mixed>  $data
     */
    private function dispatchPush(User $user, string $title, string $body, array $data): void
    {
        $this->fcm->sendToUser($user->id, $title, $body, $data);
    }
}
