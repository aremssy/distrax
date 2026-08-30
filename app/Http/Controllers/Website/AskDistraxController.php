<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\PropertyListing;
use App\Services\AskDistraxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AskDistraxController extends Controller
{
    public function ask(Request $request, PropertyListing $listing, AskDistraxService $service): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $result = $service->answer($listing, $data['question']);
        $service->log($listing, $data['question'], $result,
            $request->user(),
            $request->session()->getId(),
        );

        return back()->with('ask_distrax', $result);
    }
}
