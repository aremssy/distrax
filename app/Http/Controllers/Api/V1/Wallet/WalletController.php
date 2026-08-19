<?php

namespace App\Http\Controllers\Api\V1\Wallet;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Wallet\TopupWalletRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Models\Payment;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends ApiController
{
    /**
     * GET /api/v1/wallet
     *
     * Show the authenticated user's wallet balance.
     * Auto-creates the wallet if this is the first access.
     */
    public function show(Request $request, WalletService $walletService): JsonResponse
    {
        $wallet = $walletService->findOrCreateForUser($request->user());

        return $this->success(new WalletResource($wallet));
    }

    /**
     * GET /api/v1/wallet/transactions
     *
     * Paginated transaction history for the authenticated user's wallet.
     */
    public function transactions(Request $request, WalletService $walletService): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'in:credit,debit'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $wallet = $walletService->findOrCreateForUser($request->user());
        $perPage = min((int) $request->input('per_page', 15), 50);

        $paginator = $wallet->transactions()
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginated($paginator, WalletTransactionResource::class);
    }

    /**
     * POST /api/v1/wallet/topup
     *
     * Request a wallet top-up. Creates a pending Payment record.
     * Phase 10 gateway webhook will credit the wallet on payment confirmation.
     */
    public function topup(TopupWalletRequest $request, WalletService $walletService): JsonResponse
    {
        $user = $request->user();
        $wallet = $walletService->findOrCreateForUser($user);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => $wallet->getMorphClass(),
            'payable_id' => $wallet->id,
            'gateway' => $request->input('gateway'),
            'amount' => $request->integer('amount'),
            'currency' => $wallet->currency,
            'status' => 'pending',
            'gateway_fee' => 0,
            'discount_amount' => 0,
        ]);

        return $this->created([
            'payment_id' => $payment->id,
            'amount' => $request->integer('amount'),
            'currency' => $wallet->currency,
            'gateway' => $request->input('gateway'),
            'status' => 'pending',
            'current_balance' => (int) $wallet->balance,
            'note' => 'Complete payment via '.$request->input('gateway').' to credit your wallet.',
        ], 'Top-up request created. Complete payment to credit your wallet.');
    }
}
