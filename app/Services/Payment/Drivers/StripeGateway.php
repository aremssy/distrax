<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway implements PaymentGateway
{
    // Amounts in our system are stored in the currency's major unit (e.g. BDT taka).
    // Stripe requires the smallest unit for non-zero-decimal currencies.
    // STRIPE_AMOUNT_MULTIPLIER should be 100 for BDT/USD/EUR, 1 for zero-decimal currencies.
    private const AMOUNT_MULTIPLIER = 100;

    private function secretKey(): string
    {
        $key = setting('stripe_secret_key');
        if (! $key) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return $key;
    }

    private function webhookSecret(): string
    {
        return (string) setting('stripe_webhook_secret', '');
    }

    public function initiate(Payment $payment): array
    {
        $response = Http::withToken($this->secretKey())
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $payment->amount * self::AMOUNT_MULTIPLIER,
                'currency' => strtolower($payment->currency),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => (string) $payment->payable_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Stripe initiation failed: '.$response->json('error.message'));
        }

        $intent = $response->json();

        $payment->update(['gateway_ref' => $intent['id']]);

        return [
            'gateway' => 'stripe',
            'client_secret' => $intent['client_secret'],
            'publishable_key' => (string) setting('stripe_publishable_key', ''),
            'payment_intent_id' => $intent['id'],
            'note' => 'Use the client_secret with Stripe.js on the frontend. Card data never passes through this API.',
        ];
    }

    /**
     * Create a Stripe Checkout Session and return its hosted payment URL.
     * Used by the redirect-based website flow (no client-side JS).
     *
     * @throws RuntimeException on gateway failure
     */
    public function createCheckoutSession(Payment $payment): string
    {
        $base = rtrim((string) config('app.url'), '/');

        $response = Http::withToken($this->secretKey())
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $base.'/api/v1/payments/callback/stripe?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $base.'/api/v1/payments/callback/stripe?status=cancelled&session_id={CHECKOUT_SESSION_ID}',
                'client_reference_id' => (string) $payment->id,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'unit_amount' => $payment->amount * self::AMOUNT_MULTIPLIER,
                        'product_data' => ['name' => 'Payment #'.$payment->id],
                    ],
                ]],
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => (string) $payment->payable_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Stripe checkout session failed: '.$response->json('error.message'));
        }

        $session = $response->json();

        $payment->update(['gateway_ref' => $session['id']]);

        return $session['url'];
    }

    /**
     * Retrieve a Checkout Session to confirm its payment status on browser return.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException on gateway failure
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        $response = Http::withToken($this->secretKey())
            ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);

        if ($response->failed()) {
            throw new RuntimeException('Stripe session retrieval failed: '.$response->json('error.message'));
        }

        return $response->json();
    }

    public function validateWebhook(Request $request): bool
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $secret = $this->webhookSecret();

        if (! $sigHeader || ! $secret) {
            return false;
        }

        // Parse "t=...,v1=...,v1=..." header
        $parts = [];
        foreach (explode(',', $sigHeader) as $chunk) {
            [$k, $v] = explode('=', $chunk, 2);
            $parts[$k][] = $v;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Reject stale webhooks older than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $computed = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($computed, $sig)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $type = $data['type'] ?? '';
        $object = $data['data']['object'] ?? [];

        // Hosted Checkout Session (website redirect flow): our gateway_ref is the
        // session id, so reconcile off the session event even if the buyer never
        // returns to the site.
        if ($type === 'checkout.session.completed') {
            $rawAmount = (int) ($object['amount_total'] ?? 0);

            return new WebhookPayload(
                event: $type,
                gatewayRef: (string) ($object['id'] ?? ''),
                transactionRef: $object['payment_intent'] ?? null,
                status: ($object['payment_status'] ?? '') === 'paid' ? 'paid' : 'unknown',
                amount: (int) round($rawAmount / self::AMOUNT_MULTIPLIER),
                currency: strtoupper($object['currency'] ?? ''),
                raw: $data,
            );
        }

        $status = match ($type) {
            'payment_intent.succeeded' => 'paid',
            'payment_intent.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
            default => 'unknown',
        };

        $rawAmount = (int) ($object['amount'] ?? 0);

        return new WebhookPayload(
            event: $type,
            gatewayRef: (string) ($object['id'] ?? ''),
            transactionRef: $object['latest_charge'] ?? null,
            status: $status,
            amount: (int) round($rawAmount / self::AMOUNT_MULTIPLIER),
            currency: strtoupper($object['currency'] ?? ''),
            raw: $data,
        );
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $response = Http::withToken($this->secretKey())
            ->asForm()
            ->post('https://api.stripe.com/v1/refunds', [
                'payment_intent' => $payment->gateway_ref,
                'amount' => $amount * self::AMOUNT_MULTIPLIER,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'reason' => $reason,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Stripe refund failed: '.$response->json('error.message'));
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'stripe';
    }
}
