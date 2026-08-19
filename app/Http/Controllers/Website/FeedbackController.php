<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\PageFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /** Record a helpful / not-helpful vote for a page, one vote per visitor. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page' => ['required', 'string', 'max:60'],
            'is_helpful' => ['required', 'boolean'],
        ]);

        PageFeedback::updateOrCreate(
            [
                'page' => $validated['page'],
                'visitor_hash' => $this->visitorHash($request),
            ],
            [
                'is_helpful' => $validated['is_helpful'],
                'user_id' => $request->user()?->id,
            ],
        );

        return back()->with('feedback_success', 'Thanks for the feedback.');
    }

    /** Stable per-visitor fingerprint so a person can change their vote but not stuff the ballot. */
    private function visitorHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->user()?->id ?? $request->session()->getId(),
            $request->ip(),
        ]));
    }
}
