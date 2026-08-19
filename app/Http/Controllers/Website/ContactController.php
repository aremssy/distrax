<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('website.pages.contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create([...$request->validated(), 'status' => 'new']);

        return back()->with('success', "Thanks for reaching out. We'll get back to you shortly.");
    }
}
