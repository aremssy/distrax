<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminGlobalSearch;
use App\Services\DashboardAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardAggregator $dashboard): View
    {
        $days = (int) $request->query('range', 30);

        if (! in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $kpis = $dashboard->kpis();
        $kpiTrends = $dashboard->kpiTrends($days);
        $userGrowth = $dashboard->userGrowth($days);
        $revenueOverTime = $dashboard->revenueOverTime($days);
        $listingsByZone = $dashboard->listingsByZone();
        $bookingTrend = $dashboard->bookingTrend($days);
        $recentActivity = $dashboard->recentPlatformActivity();
        $recentListings = $dashboard->recentListings();

        return view('admin.dashboard', compact(
            'days',
            'kpis',
            'kpiTrends',
            'userGrowth',
            'revenueOverTime',
            'listingsByZone',
            'bookingTrend',
            'recentActivity',
            'recentListings',
        ));
    }

    /**
     * Global admin search across users, listings, and payments.
     */
    public function search(Request $request, AdminGlobalSearch $search): JsonResponse
    {
        $results = $search->search($request->user(), trim((string) $request->query('q', '')));

        return response()->json(['results' => $results]);
    }
}
