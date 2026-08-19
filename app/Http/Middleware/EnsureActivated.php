<?php

namespace App\Http\Middleware;

use App\Models\Activation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('installer.*') || $this->isInstalled()) {
            return $next($request);
        }

        return redirect()->route('installer.requirements');
    }

    private function isInstalled(): bool
    {
        return file_exists(storage_path('app/installed.lock')) || Activation::isActivated();
    }
}
