<?php

namespace App\Services\Payment;

use App\Models\Payment;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resolve the hosted checkout URL for a pending payment's gateway. Every gateway
 * is redirect-based: PayPal/bKash/Paystack expose a checkout URL directly, while
 * Stripe (Checkout Session) and Razorpay (Payment Link) generate a hosted link
 * on demand. Shared by the website checkout redirect and the API purchase flows.
 */
class CheckoutUrlResolver
{
    public function __construct(private GatewayFactory $gateways) {}

    /**
     * @throws InvalidArgumentException when the gateway is unsupported or misconfigured
     * @throws RuntimeException when the provider call fails
     */
    public function resolve(Payment $payment): ?string
    {
        $driver = $this->gateways->make($payment->gateway);

        return match ($payment->gateway) {
            'paypal' => $driver->initiate($payment)['approve_url'] ?? null,
            'bkash' => $driver->initiate($payment)['bkash_url'] ?? null,
            'paystack' => $driver->initiate($payment)['authorization_url'] ?? null,
            'stripe' => $driver->createCheckoutSession($payment),
            'razorpay' => $driver->createPaymentLink($payment),
            default => null,
        };
    }
}
