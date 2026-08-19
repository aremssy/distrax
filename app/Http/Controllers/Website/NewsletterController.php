<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    /** Subscribe an email address, reviving it if it had previously unsubscribed. */
    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::firstOrNew([
            'email' => $request->string('email')->lower()->value(),
        ]);

        $subscriber->source = $request->string('source')->value() ?: 'website';
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return back()->with('success', 'You are subscribed. Look out for our next update.');
    }
}
