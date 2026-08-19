<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\Payment\PaymentGateway;
use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements PaymentGateway
{
    // Paystack requires the smallest currency unit (e.g. kobo for NGN).
    private const AMOUNT_MULTIPLIER = 100;

    private function secretKey(): string
    {
        $key = setting('paystack_secret_key');
        if (! $key) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return $key;
    }

    public function initiate(Payment $payment): array
    {
        $reference = 'PAY-'.$payment->id.'-'.uniqid();

        $response = Http::withToken($this->secretKey())
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $payment->user?->email ?? 'guest@'.parse_url((string) config('app.url'), PHP_URL_HOST),
                'amount' => $payment->amount * self::AMOUNT_MULTIPLIER,
                'currency' => strtoupper($payment->currency),
                'reference' => $reference,
                'callback_url' => config('app.url').'/api/v1/payments/callback/paystack',
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => (string) $payment->payable_id,
                ],
            ]);

        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException('Paystack initialization failed: '.$response->json('message'));
        }

        $data = $response->json('data');

        $payment->update(['gateway_ref' => $reference, 'transaction_ref' => $reference]);

        return [
            'gateway' => 'paystack',
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'],
            'reference' => $reference,
        ];
    }

    /**
     * Verify a transaction by reference after the browser returns from the
     * hosted checkout. Used by the callback to confirm and activate the payment.
     *
     * @return array<string, mixed> raw Paystack verify response
     *
     * @throws RuntimeException on gateway failure
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey())
            ->get('https://api.paystack.co/transaction/verify/'.$reference);

        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException('Paystack verification failed: '.$response->json('message'));
        }

        return $response->json();
    }

    public function validateWebhook(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');

        try {
            $secret = $this->secretKey();
        } catch (RuntimeException) {
            return false;
        }

        if (! $signature) {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        $data = $request->json()->all();
        $event = $data['event'] ?? '';
        $entity = $data['data'] ?? [];

        $status = match ($event) {
            'charge.success' => 'paid',
            'refund.processed' => 'refunded',
            default => 'unknown',
        };

        $rawAmount = (int) ($entity['amount'] ?? 0);

        return new WebhookPayload(
            event: $event,
            gatewayRef: (string) ($entity['reference'] ?? ''),
            transactionRef: isset($entity['id']) ? (string) $entity['id'] : null,
            status: $status,
            amount: (int) round($rawAmount / self::AMOUNT_MULTIPLIER),
            currency: strtoupper($entity['currency'] ?? ''),
            raw: $data,
        );
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $response = Http::withToken($this->secretKey())
            ->post('https://api.paystack.co/refund', [
                'transaction' => $payment->transaction_ref,
                'amount' => $amount * self::AMOUNT_MULTIPLIER,
                'merchant_note' => $reason,
            ]);

        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException('Paystack refund failed: '.$response->json('message'));
        }

        return $response->json();
    }

    public function name(): string
    {
        return 'paystack';
    }
}
