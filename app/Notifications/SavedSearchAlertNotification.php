<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\PropertyListing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavedSearchAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PropertyListing $listing,
        public readonly SavedSearch $savedSearch,
    ) {}

    /**
     * Resolve active channels by checking the user's notification preferences
     * for the 'alert' event. Missing rows default to enabled.
     *
     * @param  User  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $prefs = NotificationPreference::where('user_id', $notifiable->id)
            ->where('event', 'alert')
            ->whereIn('channel', ['email', 'in_app'])
            ->pluck('enabled', 'channel')
            ->toArray();

        $channels = [];

        if ($prefs['email'] ?? true) {
            $channels[] = 'mail';
        }

        // 'in_app' channel: extend with a custom channel in a future phase
        // when the user-facing notifications table is available.

        return $channels;
    }

    /** @param  User  $notifiable */
    public function toMail(object $notifiable): MailMessage
    {
        $listing = $this->listing;
        $searchName = $this->savedSearch->name ?? 'your saved search';
        $listingUrl = rtrim(config('app.url'), '/').'/listings/'.$listing->id;

        return (new MailMessage)
            ->subject('New listing matches '.$searchName)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A new listing matches '.$searchName.':')
            ->line('**'.$listing->title.'**')
            ->line(number_format($listing->price).' '.setting('default_currency', 'BDT').' — '.$listing->type)
            ->action('View Listing', $listingUrl)
            ->line('You are receiving this because you enabled alerts for this search. You can disable it from your saved searches.');
    }
}
