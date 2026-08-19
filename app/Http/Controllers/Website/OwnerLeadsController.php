<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\PropertyListing;
use App\Services\OwnerLeadsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OwnerLeadsController extends Controller
{
    public function index(Request $request, OwnerLeadsService $leadsService): View
    {
        $request->validate([
            'property_listing_id' => ['sometimes', 'nullable', 'integer', 'exists:property_listings,id'],
        ]);

        $owner = $request->user();
        $listingId = $request->integer('property_listing_id') ?: null;

        $leads = $leadsService->feed($owner, $listingId);
        $summary = $leadsService->summary($owner, $listingId);

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));

        $paginated = new LengthAwarePaginator(
            $leads->forPage($page, $perPage)->values(),
            $leads->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $listings = PropertyListing::whereBelongsTo($owner, 'owner')->orderBy('title')->get(['id', 'title']);

        return view('website.owner.leads.index', [
            'leads' => $paginated,
            'summary' => $summary,
            'listings' => $listings,
            'selectedListingId' => $listingId,
        ]);
    }
}
