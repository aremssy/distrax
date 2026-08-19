<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BkashGateway implements PaymentGateway
{
    private function baseUrl(): string
    {
        return setting('bkash_sandbox', true)
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    private function appKey(): string
    {
        $key = setting('bkash_app_key');
        if (! $key) {
            throw new RuntimeException('bKash app key is not configured.');
        }

        return $key;
    }

    private function appSecret(): string
    {
        $secret = setting('bkash_app_secret');
        if (! $secret) {
            throw new RuntimeException('bKash app secret is not configured.');
        }

        return $secret;
    }

    private function grantToken(): string
    {
        $response = Http::withHeaders([
            'username' => (string) setting('bkash_username'),
            'password' => (string) setting('bkash_password'),
        ])->post($this->baseUrl().'/tokenized/checkout/token/grant', [
            'app_key' => $this->appKey(),
            'app_secret' => $this->appSecret(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('bKash token grant failed: '.$response->json('statusMessage', 'Unknown error'));
        }

        return (string) $response->json('id_token');
    }

    public function initiate(Payment $payment): array
    {
        $token = $this->grantToken();

        // bKash amounts are decimal strings in BDT taka
        $amount = number_format((float) $payment->amount, 2, '.', '');

        $response = Http::withHeaders([
            'authorization' => $token,
            'x-app-key' => $this->appKey(),
        ])->post($this->baseUrl().'/tokenized/checkout/create', [
            'mode' => '0011',
            'payerReference' => (string) $payment->user_id,
            'callbackURL' => config('app.url').'/api/v1/payments/callback/bkash',
            'amount' => $amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => 'PAY-'.(string) $payment->id,
        ]);

        $statusCode = $response->json('statusCode');

        if ($response->failed() || $statusCode !== '0000') {
            throw new RuntimeException(
                'bKash payment creation failed: '.($response->json('statusMessage') ?? 'Unknown error')
            );
        }

        $data = $response->json();

        $payment->update(['gateway_ref' => $data['paymentID']]);

        return [
            'gateway' => 'bkash',
            'payment_id' => $data['paymentID'],
            'bkash_url' => $data['bkashURL'],
        ];
    }

    /**
     * Execute a bKash payment after the user completes it on bKash's page.
     * Called from the redirect callback endpoint.
     */
    public function executePayment(string $paymentId): array
    {
        $token = $this->grantToken();

        $response = Http::withHeaders([
            'authorization' => $token,
            'x-app-key' => $this->appKey(),
        ])->post($this->baseUrl().'/tokenized/checkout/execute', [
            'paymentID' => $paymentId,
        ]);

        // bKash answers HTTP 200 even when the customer never completed the payment —
        // the real outcome is in the body. Without this check an abandoned payment could
        // be executed via the public callback and credited as paid.
        $statusCode = $response->json('statusCode');
        $transactionStatus = $response->json('transactionStatus');

        if ($response->failed() || $statusCode !== '0000' || $transactionStatus !== 'Completed') {
            throw new RuntimeException(
                'bKash execute failed: '.($response->json('statusMessage') ?? 'Payment was not completed.')
            );
        }

        return $response->json();
    }

    public function validateWebhook(Request $request): bool
    {
        // bKash IPN uses HMAC-SHA256(paymentID|status, app_secret)
        try {
            $appSecret = $this->appSecret();
        } catch (RuntimeException) {
            return false;
        }

        $paymentId = $request->query('paymentID', $request->input('paymentID', ''));
        $status = $request->query('status', $request->input('status', ''));
        $provided = $request->query('signature', $request->header('X-Bkash-Signature', ''));

        if (! $paymentId || ! $status) {
            return false;
        }

        $expected = hash_hmac('sha256', $paymentId.'|'.$status, $appSecret);

        return hash_equals($expected, $provided);
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        // bKash sends status via query string (redirect callback model)
        $paymentId = (string) ($request->query('paymentID') ?? $request->input('paymentID', ''));
        $status = (string) ($request->query('status') ?? $request->input('status', ''));
        $trxId = (string) ($request->query('trxID') ?? $request->input('trxID', ''));

        $normalised = match (strtolower($status)) {
            'completed' => 'paid',
            'failed', 'cancelled', 'cancel' => 'failed',
            default => 'unknown',
        };

        return new WebhookPayload(
            event: 'bkash.'.strtolower($status),
            gatewayRef: $paymentId,
            transactionRef: $trxId ?: null,
            status: $normalised,
            // bKash callback does not carry the amount; rely on the stored payment row
            amount: 0,
            currency: 'BDT',
            raw: $request->all(),
        );
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $token = $this->grantToken();

        $response = Http::withHeaders([
            'authorization' => $token,
            'x-app-key' => $this->appKey(),
        ])->post($this->baseUrl().'/tokenized/checkout/refund', [
            'paymentID' => $payment->gateway_ref,
            'trxID' => $payment->transaction_ref,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'reason' => $reason,
            'sku' => 'PAY-'.(string) $payment->id,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('bKash refund failed: '.$response->json('errorMessage', 'Unknown error'));
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'bkash';
    }
}
