<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\RentManagement\RentPaymentResource;
use App\Models\RentPayment;
use App\Models\RentReceipt;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RentPaymentController extends ApiController
{
    use GatesRentManagementFeatures;

    /**
     * GET /api/v1/rent-management/rent-payments
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tenancy_id' => ['sometimes', 'integer', 'exists:tenancies,id'],
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'waived'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $payments = RentPayment::whereHas('tenancy', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->with('receipt')
            ->when($request->filled('tenancy_id'), fn ($q) => $q->where('tenancy_id', $request->integer('tenancy_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('due_date')
            ->paginate($perPage);

        return $this->paginated($payments, RentPaymentResource::class);
    }

    /**
     * POST /api/v1/rent-management/rent-payments/{rentPayment}/mark-paid
     *
     * Marks a rent payment paid; issues a receipt when the 'receipts' feature is unlocked.
     */
    public function markPaid(Request $request, RentPayment $rentPayment, PostEntitlementService $entitlement): JsonResponse
    {
        $this->authorize('update', $rentPayment->tenancy);

        if ($rentPayment->status === 'paid') {
            return $this->error('This rent payment has already been marked paid.', 422);
        }

        $rentPayment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $features = $entitlement->unlockedFeatures($request->user());

        if ($this->hasFeature($features, 'receipts')) {
            RentReceipt::create([
                'rent_payment_id' => $rentPayment->id,
                'tenancy_id' => $rentPayment->tenancy_id,
                'receipt_number' => $this->nextReceiptNumber(),
                'amount' => $rentPayment->amount,
                'issued_at' => now(),
            ]);
        }

        $rentPayment->load('receipt');

        return $this->success(new RentPaymentResource($rentPayment), 'Rent payment marked paid.');
    }

    private function nextReceiptNumber(): string
    {
        return 'RCPT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }
}
