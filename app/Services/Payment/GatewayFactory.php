<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGateway;
use App\Services\Payment\Drivers\BkashGateway;
use App\Services\Payment\Drivers\PayPalGateway;
use App\Services\Payment\Drivers\PaystackGateway;
use App\Services\Payment\Drivers\RazorpayGateway;
use App\Services\Payment\Drivers\StripeGateway;
use InvalidArgumentException;

class GatewayFactory
{
    /**
     * Resolve the gateway driver by name.
     * Gateway keys are always read from settings (admin-configurable), never hardcoded.
     */
    public function make(string $gateway): PaymentGateway
    {
        return match (strtolower(trim($gateway))) {
            'stripe' => new StripeGateway,
            'paypal' => new PayPalGateway,
            'bkash' => new BkashGateway,
            'razorpay' => new RazorpayGateway,
            'paystack' => new PaystackGateway,
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Return the list of gateways enabled in admin settings.
     * Admin sets: Settings → active_payment_gateways → "stripe,bkash"
     *
     * @return list<string>
     */
    public function activeGateways(): array
    {
        $raw = setting('active_payment_gateways', 'stripe');

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }

    /**
     * Check that a gateway string is currently enabled.
     */
    public function isActive(string $gateway): bool
    {
        return in_array(strtolower($gateway), $this->activeGateways(), true);
    }
}
