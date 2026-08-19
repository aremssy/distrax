<?php

namespace App\Contracts\Payment;

use App\DTO\WebhookPayload;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Initiate a payment with the gateway.
     *
     * Returns gateway-specific checkout data, e.g.:
     *  - Stripe: { client_secret, publishable_key }
     *  - PayPal: { approve_url, order_id }
     *  - bKash:  { bkash_url, payment_id }
     *
     * MUST update $payment->gateway_ref before returning.
     *
     * @return array<string, mixed>
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify the incoming webhook signature.
     * Return false to abort with 401 and stop processing.
     */
    public function validateWebhook(Request $request): bool;

    /**
     * Parse the webhook body into a normalised payload DTO.
     * Called only after validateWebhook() returns true.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload;

    /**
     * Execute a refund through the gateway.
     * Amount is in the same unit as Payment::$amount.
     *
     * @return array<string, mixed> raw gateway response
     *
     * @throws \RuntimeException on gateway failure
     */
    public function refund(Payment $payment, int $amount, string $reason): array;

    /**
     * Return the canonical gateway identifier (stripe | paypal | bkash | razorpay | paystack).
     */
    public function name(): string;
}
