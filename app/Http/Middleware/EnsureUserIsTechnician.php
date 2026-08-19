<?php

namespace App\Http\Middleware;

use App\Models\Technician;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict the technician dashboard to users who actually have a profile.
 *
 * A user without one is sent to the application form instead of a 403, since
 * "you have not applied yet" is a normal state rather than an error.
 */
class EnsureUserIsTechnician
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $technician = Technician::where('user_id', $request->user()?->id)->first();

        if (! $technician) {
            return redirect()->route('technician.apply')
                ->with('info', 'Apply as a technician to access this area.');
        }

        $request->attributes->set('technician', $technician);

        return $next($request);
    }
}
