<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\VerificationScore;
use Illuminate\View\View;

class VerificationPassportController extends Controller
{
    /** Public QR-scan destination: /verify/{reference} — no seller PII, no document access. */
    public function show(string $reference): View
    {
        $score = VerificationScore::with('listing:id,title,slug')
            ->where('reference_id', $reference)
            ->firstOrFail();

        return view('website.pages.verification_passport', compact('score'));
    }
}
