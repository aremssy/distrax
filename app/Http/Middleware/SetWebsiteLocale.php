<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetWebsiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin/*')) {
            $code = $request->session()->get('locale');

            if ($code && Language::findActiveByCode($code) !== null) {
                App::setLocale($code);
            }
        }

        return $next($request);
    }
}
