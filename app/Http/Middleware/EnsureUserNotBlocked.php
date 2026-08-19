<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject requests from a user who was blocked *after* authenticating.
 *
 * Login checks `is_blocked`, but a user blocked mid-session keeps a valid Sanctum
 * token or web session; without this they would retain full access until it expires.
 * API requests get their token revoked and a 403 envelope; web requests are logged
 * out, their session invalidated, and redirected to the login page.
 */
class EnsureUserNotBlocked
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->is_blocked) {
            return $next($request);
        }

        $message = 'Your account has been blocked. Please contact support.';

        if ($request->is('api/*') || $request->expectsJson()) {
            $token = $user->currentAccessToken();

            if ($token instanceof Model) {
                $token->delete();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => null,
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
