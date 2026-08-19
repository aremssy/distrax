<?php

namespace App\Http\Controllers\Website\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('website.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if ($request->user()->is_blocked) {
            Auth::logout();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Your account has been suspended.',
            ]);
        }

        $request->session()->regenerate();
        $request->user()->update(['last_active_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }
}
