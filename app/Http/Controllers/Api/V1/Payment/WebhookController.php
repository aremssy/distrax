<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\GatewayFactory;
use App\Services\Payment\PaymentActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class WebhookController extends ApiController
{
    public function __construct(
        public GatewayFactory $gatewayFactory,
        public PaymentActivationService $activationService,
    ) {}

    /**
     * POST /api/v1/webhooks/{gateway}
     *
     * Receive a server-to-server webhook from a payment gateway.
     * Validates the signature, finds the matching Payment, and activates the payable.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        try {
            $driver = $this->gatewayFactory->make($gateway);
        } catch (InvalidArgumentException) {
            return $this->error('Unknown gateway.', 404);
        }

        if (! $driver->validateWebhook($request)) {
            Log::warning("Webhook signature validation failed for gateway: {$gateway}", [
                'ip' => $request->ip(),
            ]);

            return $this->error('Invalid webhook signature.', 401);
        }

        $payload = $driver->parseWebhookPayload($request);

        if ($payload->status === 'unknown') {
            // Unrecognised event type — acknowledge without processing.
            return $this->success(null, 'Event type ignored.');
        }

        $payment = Payment::where('gateway_ref', $payload->gatewayRef)->first();

        if (! $payment) {
            // Gateway may have sent an event for a payment not in our system.
            return $this->success(null, 'Payment not found; skipped.');
        }

        // Idempotency: already in the target state — no reprocessing needed.
        if ($payment->status === $payload->status) {
            return $this->success(null, 'Already in the target state.');
        }

        DB::transaction(function () use ($payment, $payload) {
            $updates = [
                'status' => $payload->status,
                'gateway_response' => $payload->raw,
            ];

            if ($payload->transactionRef) {
                $updates['transaction_ref'] = $payload->transactionRef;
            }

            if ($payload->status === 'paid') {
                $updates['paid_at'] = now();
            }

            $payment->update($updates);

            if ($payload->status === 'paid') {
                $this->activationService->activate($payment->load('payable'));
            }
        });

        Log::info("Webhook processed for gateway {$gateway}: payment #{$payment->id} → {$payload->status}");

        return $this->success(null, 'Webhook processed.');
    }

    /**
     * GET /api/v1/payments/callback/{gateway}
     *
     * Browser redirect callback after the user completes (or cancels) payment on the
     * gateway's hosted page. Captures the order (PayPal), executes the payment (bKash),
     * or verifies the transaction (Paystack).
     *
     * API clients (Accept: application/json) receive a JSON PaymentResource; browsers
     * are redirected back to the Wallet & Plans page with a `?checkout=` outcome marker.
     */
    public function callback(Request $request, string $gateway): JsonResponse|RedirectResponse
    {
        try {
            $driver = $this->gatewayFactory->make($gateway);
        } catch (InvalidArgumentException) {
            return $this->callbackResponse($request, $this->error('Unknown gateway.', 404), 'failed');
        }

        return match ($gateway) {
            'paypal' => $this->handlePayPalCallback($request, $driver),
            'bkash' => $this->handleBkashCallback($request, $driver),
            'paystack' => $this->handlePaystackCallback($request, $driver),
            'stripe' => $this->handleStripeCallback($request, $driver),
            'razorpay' => $this->handleRazorpayCallback($request, $driver),
            default => $this->callbackResponse($request, $this->error('No callback handler for this gateway.', 422), 'failed'),
        };
    }

    private function handlePayPalCallback(Request $request, mixed $driver): JsonResponse|RedirectResponse
    {
        // PayPal appends ?token=ORDER_ID&PayerID=PAYER_ID to the return URL.
        $orderId = $request->query('token');

        if (! $orderId) {
            return $this->callbackResponse($request, $this->error('Missing PayPal order ID in callback.', 422), 'failed');
        }

        if ($request->query('status') === 'cancelled' || ! $request->query('PayerID')) {
            $payment = Payment::where('gateway_ref', $orderId)->first();
            if ($payment) {
                $payment->update(['status' => 'failed', 'gateway_response' => $request->all()]);
            }

            return $this->callbackResponse($request, $this->error('PayPal payment was cancelled.', 422), 'cancelled');
        }

        $payment = Payment::where('gateway_ref', $orderId)->first();

        if (! $payment) {
            return $this->callbackResponse($request, $this->error('Payment not found.', 404), 'failed');
        }

        if ($payment->status === 'paid') {
            return $this->callbackResponse($request, $this->success(new PaymentResource($payment->load('refunds')), 'Payment already completed.'), 'success');
        }

        try {
            $captureResult = $driver->captureOrder($orderId);
            $captureId = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            DB::transaction(function () use ($payment, $captureResult, $captureId) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => $captureResult,
                    'transaction_ref' => $captureId,
                ]);

                $this->activationService->activate($payment->load('payable'));
            });
        } catch (RuntimeException $e) {
            return $this->callbackResponse($request, $this->error($e->getMessage(), 502), 'failed');
        }

        return $this->callbackResponse($request, $this->success(new PaymentResource($payment->fresh(['refunds'])), 'Payment completed successfully.'), 'success');
    }

    private function handleBkashCallback(Request $request, mixed $driver): JsonResponse|RedirectResponse
    {
        $paymentId = $request->query('paymentID') ?? $request->input('paymentID');
        $status = strtolower((string) ($request->query('status') ?? $request->input('status', '')));

        if (! $paymentId) {
            return $this->callbackResponse($request, $this->error('Missing bKash paymentID in callback.', 422), 'failed');
        }

        $payment = Payment::where('gateway_ref', $paymentId)->first();

        if (! $payment) {
            return $this->callbackResponse($request, $this->error('Payment not found.', 404), 'failed');
        }

        if (in_array($status, ['cancel', 'failure', 'failed', 'cancelled'], true)) {
            $payment->update(['status' => 'failed', 'gateway_response' => $request->all()]);

            return $this->callbackResponse($request, $this->error('bKash payment was cancelled or failed.', 422), 'cancelled');
        }

        if ($payment->status === 'paid') {
            return $this->callbackResponse($request, $this->success(new PaymentResource($payment->load('refunds')), 'Payment already completed.'), 'success');
        }

        try {
            $executeResult = $driver->executePayment($paymentId);
            $trxId = $executeResult['trxID'] ?? null;

            DB::transaction(function () use ($payment, $executeResult, $trxId) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => $executeResult,
                    'transaction_ref' => $trxId,
                ]);

                $this->activationService->activate($payment->load('payable'));
            });
        } catch (RuntimeException $e) {
            return $this->callbackResponse($request, $this->error($e->getMessage(), 502), 'failed');
        }

        return $this->callbackResponse($request, $this->success(new PaymentResource($payment->fresh(['refunds'])), 'Payment completed successfully.'), 'success');
    }

    private function handlePaystackCallback(Request $request, mixed $driver): JsonResponse|RedirectResponse
    {
        // Paystack appends ?reference=REF&trxref=REF to the callback URL.
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return $this->callbackResponse($request, $this->error('Missing Paystack reference in callback.', 422), 'failed');
        }

        $payment = Payment::where('gateway_ref', $reference)->first();

        if (! $payment) {
            return $this->callbackResponse($request, $this->error('Payment not found.', 404), 'failed');
        }

        if ($payment->status === 'paid') {
            return $this->callbackResponse($request, $this->success(new PaymentResource($payment->load('refunds')), 'Payment already completed.'), 'success');
        }

        try {
            $result = $driver->verifyTransaction($reference);

            if (($result['data']['status'] ?? '') !== 'success') {
                $payment->update(['status' => 'failed', 'gateway_response' => $result]);

                return $this->callbackResponse($request, $this->error('Paystack payment was not successful.', 422), 'failed');
            }

            DB::transaction(function () use ($payment, $result) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => $result,
                    'transaction_ref' => (string) ($result['data']['id'] ?? $payment->transaction_ref),
                ]);

                $this->activationService->activate($payment->load('payable'));
            });
        } catch (RuntimeException $e) {
            return $this->callbackResponse($request, $this->error($e->getMessage(), 502), 'failed');
        }

        return $this->callbackResponse($request, $this->success(new PaymentResource($payment->fresh(['refunds'])), 'Payment completed successfully.'), 'success');
    }

    private function handleStripeCallback(Request $request, mixed $driver): JsonResponse|RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return $this->callbackResponse($request, $this->error('Missing Stripe session ID in callback.', 422), 'failed');
        }

        $payment = Payment::where('gateway_ref', $sessionId)->first();

        if (! $payment) {
            return $this->callbackResponse($request, $this->error('Payment not found.', 404), 'failed');
        }

        if ($request->query('status') === 'cancelled') {
            return $this->callbackResponse($request, $this->error('Stripe payment was cancelled.', 422), 'cancelled');
        }

        if ($payment->status === 'paid') {
            return $this->callbackResponse($request, $this->success(new PaymentResource($payment->load('refunds')), 'Payment already completed.'), 'success');
        }

        try {
            $session = $driver->retrieveCheckoutSession($sessionId);

            if (($session['payment_status'] ?? '') !== 'paid') {
                return $this->callbackResponse($request, $this->error('Stripe payment is not yet complete.', 422), 'failed');
            }

            DB::transaction(function () use ($payment, $session) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => $session,
                    'transaction_ref' => $session['payment_intent'] ?? null,
                ]);

                $this->activationService->activate($payment->load('payable'));
            });
        } catch (RuntimeException $e) {
            return $this->callbackResponse($request, $this->error($e->getMessage(), 502), 'failed');
        }

        return $this->callbackResponse($request, $this->success(new PaymentResource($payment->fresh(['refunds'])), 'Payment completed successfully.'), 'success');
    }

    private function handleRazorpayCallback(Request $request, mixed $driver): JsonResponse|RedirectResponse
    {
        $linkId = $request->query('razorpay_payment_link_id');
        $status = strtolower((string) $request->query('razorpay_payment_link_status', ''));

        if (! $linkId) {
            return $this->callbackResponse($request, $this->error('Missing Razorpay payment link ID in callback.', 422), 'failed');
        }

        $payment = Payment::where('gateway_ref', $linkId)->first();

        if (! $payment) {
            return $this->callbackResponse($request, $this->error('Payment not found.', 404), 'failed');
        }

        if (! $driver->verifyPaymentLinkSignature($request->query())) {
            return $this->callbackResponse($request, $this->error('Invalid Razorpay signature.', 401), 'failed');
        }

        if ($payment->status === 'paid') {
            return $this->callbackResponse($request, $this->success(new PaymentResource($payment->load('refunds')), 'Payment already completed.'), 'success');
        }

        if ($status !== 'paid') {
            return $this->callbackResponse($request, $this->error('Razorpay payment was not completed.', 422), 'cancelled');
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_response' => $request->query(),
                'transaction_ref' => $request->query('razorpay_payment_id'),
            ]);

            $this->activationService->activate($payment->load('payable'));
        });

        return $this->callbackResponse($request, $this->success(new PaymentResource($payment->fresh(['refunds'])), 'Payment completed successfully.'), 'success');
    }

    /**
     * API clients get the JSON response; browsers are redirected to the Wallet &
     * Plans page with a `?checkout=` outcome marker the page turns into a flash.
     */
    private function callbackResponse(Request $request, JsonResponse $json, string $outcome): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $json;
        }

        return redirect()->route('dashboard.wallet', ['checkout' => $outcome]);
    }
}
