<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('admin.login')
                ->with('error', 'Please sign in to access the admin panel.');
        }

        if (! $request->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
