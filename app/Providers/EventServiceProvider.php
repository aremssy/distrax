<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\NotifiesAdminsObserver;
use App\Services\NotificationCenter;
use Illuminate\Auth\Events\Registered;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the application-wide notification write path: the config-driven activity
 * observer plus authentication and background-job hooks. Kept separate from
 * AppServiceProvider so the notification surface lives in one obvious place.
 */
class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerActivityObservers();
        $this->registerAuthNotifications();
        $this->registerJobNotifications();
    }

    /**
     * Attach the generic admin-notification observer to every model declared in
     * config/notifications.php.
     */
    private function registerActivityObservers(): void
    {
        foreach (array_keys((array) config('notifications.models', [])) as $model) {
            if (class_exists($model)) {
                $model::observe(NotifiesAdminsObserver::class);
            }
        }
    }

    /**
     * On a genuine self-registration (the framework's Registered event): welcome the new
     * user and let the admins know. This is the precise new-user signal — admin-created
     * accounts and test fixtures do not fire it, so they generate no "new user" noise.
     */
    private function registerAuthNotifications(): void
    {
        Event::listen(Registered::class, function (Registered $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $notifications = app(NotificationCenter::class);

            $notifications->toAdmins(
                type: 'new_user_registered',
                title: 'New user registered',
                body: $event->user->name,
                link: Route::has('admin.users.show')
                    ? route('admin.users.show', $event->user)
                    : null,
                permission: 'users.view',
            );

            $notifications->toUser(
                user: $event->user,
                type: 'new_user_registered',
                title: 'Welcome aboard',
                body: 'Your account has been created successfully. Explore listings and manage everything from your dashboard.',
            );
        });
    }

    /**
     * Surface background-job failures to the admins so silent queue errors don't go
     * unnoticed in production.
     */
    private function registerJobNotifications(): void
    {
        Queue::failing(function (JobFailed $event): void {
            app(NotificationCenter::class)->toAdmins(
                type: 'system',
                title: 'Background job failed',
                body: class_basename($event->job->resolveName()).' failed: '.$event->exception->getMessage(),
                permission: 'settings.view',
            );
        });
    }
}
