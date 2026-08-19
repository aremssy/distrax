<?php

namespace App\Http\Middleware;

use App\Models\Activation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the web installer once the application has been installed.
 *
 * The GET installer steps already redirect when installed, but the POST
 * handlers (saveDatabase, saveAdmin, saveSettings, verifyLicense) did not —
 * leaving them callable by anonymous users on a live instance, which allowed
 * an .env/APP_KEY rewrite, a destructive re-migrate, and creation of a fresh
 * super-admin session. This middleware closes that hole for the whole group.
 */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalled()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }

    private function isInstalled(): bool
    {
        return file_exists(storage_path('app/installed.lock')) || Activation::isActivated();
    }
}
