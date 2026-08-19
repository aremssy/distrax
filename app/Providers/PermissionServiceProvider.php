<?php

namespace App\Providers;

use App\Models\User;
use App\Support\PermissionList;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Super-admin bypasses all gate checks
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Register every permission slug as a gate definition
        foreach (PermissionList::all() as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        // Point password-reset emails to the correct portal: staff accounts
        // (those with an assigned role) reset through the admin panel, while
        // ordinary members reset through the public website.
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $isStaff = $notifiable instanceof User && $notifiable->role_id !== null;

            return route($isStaff ? 'admin.password.reset' : 'password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }
}
