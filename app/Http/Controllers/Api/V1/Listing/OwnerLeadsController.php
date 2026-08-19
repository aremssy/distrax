<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\OwnerLeadsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OwnerLeadsController extends ApiController
{
    public function __construct(private OwnerLeadsService $leads) {}

    /**
     * GET /api/v1/owner/leads
     *
     * A unified, listing-filterable feed of visit requests, contact reveals,
     * and inbound conversations across the authenticated owner's listings.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'property_listing_id' => ['sometimes', 'integer', 'exists:property_listings,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);
        $page = max(1, (int) $request->input('page', 1));

        $leads = $this->leads->feed($request->user(), $request->integer('property_listing_id') ?: null);

        $items = $leads->forPage($page, $perPage)
            ->map(fn (array $lead) => [...$lead, 'occurred_at' => $lead['occurred_at']?->toIso8601String()])
            ->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $leads->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $paginator->items(),
            'errors' => null,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/owner/leads/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'property_listing_id' => ['sometimes', 'integer', 'exists:property_listings,id'],
        ]);

        return $this->success(
            $this->leads->summary($request->user(), $request->integer('property_listing_id') ?: null)
        );
    }
}
