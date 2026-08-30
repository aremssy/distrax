<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * P3 stub — Distrax Escrow & Distrax Invest surface informational screens only.
 * No money movement is implemented until regulatory sign-off (config: features.escrow_invest).
 */
class EscrowInvestController extends Controller
{
    public function index(Request $request): View
    {
        $enabled = (bool) config('features.escrow_invest');

        $pooledOpportunities = $enabled
            ? $this->pooledOpportunities($request)
            : [];

        return view('website.dashboard.escrow-invest', [
            'enabled' => $enabled,
            'pooledOpportunities' => $pooledOpportunities,
        ]);
    }

    /**
     * Read-only, informational "pooled opportunities". No transacting capability.
     *
     * @return array<int, array{id: int, title: string, note: string}>
     */
    private function pooledOpportunities(Request $request): array
    {
        return [];
    }
}
