<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function setLocale(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::exists('languages', 'code')->where('is_active', true)],
        ]);

        $request->session()->put('locale', $request->string('locale')->value());

        return back();
    }

    public function setCurrency(Request $request): RedirectResponse
    {
        $request->validate([
            'currency' => ['required', 'string', Rule::exists('currencies', 'code')->where('is_active', true)],
        ]);

        $request->session()->put('currency_code', $request->string('currency')->value());

        return back();
    }

    /**
     * Toggle low-data mode, which suppresses non-critical media (hero/background
     * imagery, secondary gallery images, map tiles) to save bandwidth. Session-backed
     * so it works for guests as well as signed-in users.
     */
    public function toggleLowData(Request $request): RedirectResponse
    {
        $request->session()->put('low_data', ! $request->session()->get('low_data', false));

        return back();
    }
}
