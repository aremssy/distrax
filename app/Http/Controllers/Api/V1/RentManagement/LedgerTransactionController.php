<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\RentManagement\StoreLedgerTransactionRequest;
use App\Http\Resources\Api\V1\RentManagement\LedgerTransactionResource;
use App\Models\LedgerTransaction;
use App\Models\PropertyListing;
use App\Models\Tenancy;
use App\Services\PostEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LedgerTransactionController extends ApiController
{
    use GatesRentManagementFeatures;

    /**
     * GET /api/v1/rent-management/ledger-transactions
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', Rule::in(['income', 'expense'])],
            'property_listing_id' => ['sometimes', 'integer', 'exists:property_listings,id'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $transactions = $this->baseQuery($request)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('property_listing_id'), fn ($q) => $q->where('property_listing_id', $request->integer('property_listing_id')))
            ->orderByDesc('occurred_on')
            ->paginate($perPage);

        return $this->paginated($transactions, LedgerTransactionResource::class);
    }

    /**
     * GET /api/v1/rent-management/ledger-transactions/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $query = $this->baseQuery($request);

        $income = (int) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (int) (clone $query)->where('type', 'expense')->sum('amount');

        return $this->success([
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ]);
    }

    public function store(StoreLedgerTransactionRequest $request, PostEntitlementService $entitlement): JsonResponse
    {
        $user = $request->user();
        $features = $entitlement->unlockedFeatures($user);
        $requiredFeature = $request->input('type') === 'income' ? 'income_tracking' : 'expense_tracking';

        if ($response = $this->featureGate($features, $requiredFeature, "Recording {$request->input('type')} requires the {$requiredFeature} feature.")) {
            return $response;
        }

        if ($request->filled('property_listing_id')) {
            $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));
            $this->authorize('update', $listing);
        }

        if ($request->filled('tenancy_id')) {
            $tenancy = Tenancy::findOrFail($request->integer('tenancy_id'));
            $this->authorize('view', $tenancy);
        }

        $transaction = LedgerTransaction::create([
            'owner_id' => $user->id,
            ...$request->validated(),
        ]);

        return $this->created(new LedgerTransactionResource($transaction), 'Transaction recorded.');
    }

    private function baseQuery(Request $request): Builder
    {
        return LedgerTransaction::where('owner_id', $request->user()->id)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_on', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_on', '<=', $request->date('to')));
    }
}
