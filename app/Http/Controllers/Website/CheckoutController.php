<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\CheckoutUrlResolver;
use App\Services\Payment\GatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private GatewayFactory $gateways,
        private CheckoutUrlResolver $checkoutUrls,
    ) {}

    /**
     * Create the gateway checkout for a pending payment and redirect the browser
     * to the provider's hosted payment page. Every gateway is redirect-based:
     * PayPal/bKash/Paystack expose a checkout URL directly, while Stripe (Checkout
     * Session) and Razorpay (Payment Link) generate a hosted link on demand.
     *
     * The user returns to /api/v1/payments/callback/{gateway}, which verifies the
     * payment and redirects back to the Wallet & Plans page.
     */
    public function start(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status === 'paid') {
            return redirect()->route('dashboard.wallet')->with('success', 'This payment is already complete.');
        }

        if ($payment->status !== 'pending') {
            return redirect()->route('dashboard.wallet')->with('error', 'This payment can no longer be processed.');
        }

        if (! $this->gateways->isActive($payment->gateway)) {
            return redirect()->route('dashboard.wallet')->with('error', 'The selected payment gateway is not available.');
        }

        try {
            $url = $this->checkoutUrls->resolve($payment);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return redirect()->route('dashboard.wallet')->with('error', $exception->getMessage());
        }

        if (! $url) {
            return redirect()->route('dashboard.wallet')->with('error', 'Unsupported payment gateway.');
        }

        return redirect()->away($url);
    }
}
