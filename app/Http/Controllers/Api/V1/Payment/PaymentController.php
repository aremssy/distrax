<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Payment\InitiatePaymentRequest;
use App\Http\Requests\Api\V1\Payment\RefundRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Http\Resources\Api\V1\RefundResource;
use App\Models\Payment;
use App\Services\IdempotencyService;
use App\Services\Payment\GatewayFactory;
use App\Services\Payment\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PaymentController extends ApiController
{
    public function __construct(
        public GatewayFactory $gatewayFactory,
        public RefundService $refundService,
        public IdempotencyService $idempotency,
    ) {}

    /**
     * POST /api/v1/payments/{payment}/initiate
     *
     * Initiate a pending payment through the chosen gateway.
     * Idempotency: repeat calls with the same X-Idempotency-Key return the cached response.
     */
    public function initiate(InitiatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('initiate', $payment);

        if ($payment->status !== 'pending') {
            return $this->success(new PaymentResource($payment), 'This payment has already been processed.');
        }

        $gateway = $request->input('gateway');
        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (! $this->gatewayFactory->isActive($gateway)) {
            return $this->error("Payment gateway '{$gateway}' is not currently active.", 422);
        }

        if (! $idempotencyKey) {
            return $this->initiateWithGateway($payment, $gateway);
        }

        $outcome = $this->idempotency->remember(
            $request->user(),
            "payment:initiate:{$payment->id}",
            $idempotencyKey,
            fn () => $this->buildInitiateOutcome($payment, $gateway)
        );

        return response()->json($outcome['body'], $outcome['status']);
    }

    /**
     * GET /api/v1/payments/{payment}
     *
     * Return the current status of a payment (owner only).
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load('refunds');

        return $this->success(new PaymentResource($payment));
    }

    /**
     * POST /api/v1/payments/{payment}/refund
     *
     * Request a refund for a paid payment.
     * Partial refunds are supported by passing an amount.
     */
    public function refund(RefundRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);

        $amount = $request->integer('amount', 0);

        try {
            $refund = $this->refundService->refund(
                $payment,
                $request->user(),
                $amount,
                $request->input('reason')
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(new RefundResource($refund), 'Refund processed successfully.');
    }

    private function initiateWithGateway(Payment $payment, string $gateway): JsonResponse
    {
        try {
            $driver = $this->gatewayFactory->make($gateway);
            $payment->update(['gateway' => $gateway]);
            $result = array_merge(['payment_id' => $payment->id], $driver->initiate($payment));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 502);
        }

        return $this->success($result, 'Payment initiated. Follow gateway instructions to complete.');
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function buildInitiateOutcome(Payment $payment, string $gateway): array
    {
        $response = $this->initiateWithGateway($payment, $gateway);

        return ['status' => $response->getStatusCode(), 'body' => $response->getData(true)];
    }
}
