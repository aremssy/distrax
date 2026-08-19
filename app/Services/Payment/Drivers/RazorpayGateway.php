<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayGateway implements PaymentGateway
{
    // Razorpay requires the smallest currency unit (e.g. paise for INR).
    private const AMOUNT_MULTIPLIER = 100;

    private function keyId(): string
    {
        $key = setting('razorpay_key_id');
        if (! $key) {
            throw new RuntimeException('Razorpay key ID is not configured.');
        }

        return $key;
    }

    private function keySecret(): string
    {
        $secret = setting('razorpay_key_secret');
        if (! $secret) {
            throw new RuntimeException('Razorpay key secret is not configured.');
        }

        return $secret;
    }

    private function webhookSecret(): string
    {
        return (string) setting('razorpay_webhook_secret', '');
    }

    public function initiate(Payment $payment): array
    {
        $response = Http::withBasicAuth($this->keyId(), $this->keySecret())
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $payment->amount * self::AMOUNT_MULTIPLIER,
                'currency' => strtoupper($payment->currency),
                'receipt' => 'PAY-'.$payment->id,
                'notes' => [
                    'payment_id' => (string) $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => (string) $payment->payable_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Razorpay order creation failed: '.$response->json('error.description'));
        }

        $order = $response->json();

        $payment->update(['gateway_ref' => $order['id']]);

        return [
            'gateway' => 'razorpay',
            'order_id' => $order['id'],
            'key_id' => $this->keyId(),
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'note' => 'Use order_id with Razorpay Checkout on the frontend. Card data never passes through this API.',
        ];
    }

    /**
     * Create a Razorpay Payment Link and return its hosted short URL.
     * Used by the redirect-based website flow (no client-side JS).
     *
     * @throws RuntimeException on gateway failure
     */
    public function createPaymentLink(Payment $payment): string
    {
        $base = rtrim((string) config('app.url'), '/');

        $response = Http::withBasicAuth($this->keyId(), $this->keySecret())
            ->post('https://api.razorpay.com/v1/payment_links', [
                'amount' => $payment->amount * self::AMOUNT_MULTIPLIER,
                'currency' => strtoupper($payment->currency),
                'description' => 'Payment #'.$payment->id,
                'callback_url' => $base.'/api/v1/payments/callback/razorpay',
                'callback_method' => 'get',
                'notes' => [
                    'payment_id' => (string) $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => (string) $payment->payable_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Razorpay payment link failed: '.$response->json('error.description'));
        }

        $link = $response->json();

        $payment->update(['gateway_ref' => $link['id']]);

        return $link['short_url'];
    }

    /**
     * Verify the signature Razorpay appends to the payment-link callback.
     *
     * @param  array<string, mixed>  $params
     */
    public function verifyPaymentLinkSignature(array $params): bool
    {
        $payload = ($params['razorpay_payment_link_id'] ?? '').'|'.
            ($params['razorpay_payment_link_reference_id'] ?? '').'|'.
            ($params['razorpay_payment_link_status'] ?? '').'|'.
            ($params['razorpay_payment_id'] ?? '');

        $expected = hash_hmac('sha256', $payload, $this->keySecret());

        return hash_equals($expected, (string) ($params['razorpay_signature'] ?? ''));
    }

    public function validateWebhook(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $secret = $this->webhookSecret();

        if (! $signature || ! $secret) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $event = $data['event'] ?? '';
        $entity = $data['payload']['payment']['entity'] ?? [];

        // Hosted Payment Link (website redirect flow): our gateway_ref is the
        // payment-link id, so reconcile off the link event even if the buyer
        // never returns to the site.
        if ($event === 'payment_link.paid') {
            $link = $data['payload']['payment_link']['entity'] ?? [];
            $rawAmount = (int) ($link['amount'] ?? ($entity['amount'] ?? 0));

            return new WebhookPayload(
                event: $event,
                gatewayRef: (string) ($link['id'] ?? ''),
                transactionRef: $entity['id'] ?? null,
                status: 'paid',
                amount: (int) round($rawAmount / self::AMOUNT_MULTIPLIER),
                currency: strtoupper($link['currency'] ?? ($entity['currency'] ?? '')),
                raw: $data,
            );
        }

        $status = match ($event) {
            'payment.captured' => 'paid',
            'payment.failed' => 'failed',
            'refund.processed' => 'refunded',
            default => 'unknown',
        };

        $rawAmount = (int) ($entity['amount'] ?? 0);

        return new WebhookPayload(
            event: $event,
            gatewayRef: (string) ($entity['order_id'] ?? ''),
            transactionRef: $entity['id'] ?? null,
            status: $status,
            amount: (int) round($rawAmount / self::AMOUNT_MULTIPLIER),
            currency: strtoupper($entity['currency'] ?? ''),
            raw: $data,
        );
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $response = Http::withBasicAuth($this->keyId(), $this->keySecret())
            ->post("https://api.razorpay.com/v1/payments/{$payment->transaction_ref}/refund", [
                'amount' => $amount * self::AMOUNT_MULTIPLIER,
                'notes' => [
                    'payment_id' => (string) $payment->id,
                    'reason' => $reason,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Razorpay refund failed: '.$response->json('error.description'));
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'razorpay';
    }
}
