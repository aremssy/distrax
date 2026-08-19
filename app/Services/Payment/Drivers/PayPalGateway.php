<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalGateway implements PaymentGateway
{
    private function baseUrl(): string
    {
        return setting('paypal_sandbox', true)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function accessToken(): string
    {
        $clientId = setting('paypal_client_id');
        $secret = setting('paypal_client_secret');

        if (! $clientId || ! $secret) {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PayPal authentication failed.');
        }

        return (string) $response->json('access_token');
    }

    public function initiate(Payment $payment): array
    {
        $token = $this->accessToken();

        // PayPal amounts are decimal strings (major unit, e.g. "1000.00" for 1000 BDT)
        $amountValue = number_format((float) $payment->amount, 2, '.', '');

        $response = Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => 'pay-'.(string) $payment->id])
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => $amountValue,
                    ],
                    'custom_id' => (string) $payment->id,
                    'description' => "Payment #{$payment->id}",
                ]],
                'application_context' => [
                    'return_url' => config('app.url').'/api/v1/payments/callback/paypal',
                    'cancel_url' => config('app.url').'/api/v1/payments/callback/paypal?status=cancelled',
                    'brand_name' => config('app.name'),
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PayPal order creation failed: '.$response->json('message'));
        }

        $order = $response->json();
        $approveUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? '';

        $payment->update(['gateway_ref' => $order['id']]);

        return [
            'gateway' => 'paypal',
            'order_id' => $order['id'],
            'approve_url' => $approveUrl,
        ];
    }

    /**
     * Capture a PayPal order after user approval (called from callback endpoint).
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->post($this->baseUrl()."/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            throw new RuntimeException('PayPal capture failed: '.$response->json('message'));
        }

        return $response->json();
    }

    public function validateWebhook(Request $request): bool
    {
        $webhookId = setting('paypal_webhook_id');

        if (! $webhookId) {
            return false;
        }

        try {
            $token = $this->accessToken();
        } catch (RuntimeException) {
            return false;
        }

        $response = Http::withToken($token)
            ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->json()->all(),
            ]);

        return $response->json('verification_status') === 'SUCCESS';
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $type = $data['event_type'] ?? '';
        $resource = $data['resource'] ?? [];

        $status = match ($type) {
            'PAYMENT.CAPTURE.COMPLETED' => 'paid',
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => 'failed',
            'PAYMENT.CAPTURE.REFUNDED' => 'refunded',
            default => 'unknown',
        };

        $amountValue = (float) ($resource['amount']['value'] ?? 0);
        $currency = strtoupper($resource['amount']['currency_code'] ?? '');

        // Gateway_ref for PayPal orders comes from supplementary_data
        $gatewayRef = $resource['supplementary_data']['related_ids']['order_id']
            ?? $resource['id']
            ?? '';

        return new WebhookPayload(
            event: $type,
            gatewayRef: (string) $gatewayRef,
            transactionRef: (string) ($resource['id'] ?? ''),
            status: $status,
            amount: (int) round($amountValue),
            currency: $currency,
            raw: $data,
        );
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $token = $this->accessToken();
        $captureId = $payment->transaction_ref ?: $payment->gateway_ref;

        $response = Http::withToken($token)
            ->post($this->baseUrl()."/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'value' => number_format((float) $amount, 2, '.', ''),
                    'currency_code' => $payment->currency,
                ],
                'note_to_payer' => $reason,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PayPal refund failed: '.$response->json('message'));
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'paypal';
    }
}
