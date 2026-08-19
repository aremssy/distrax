<?php

namespace App\DTO;

/**
 * Normalised payload from any payment gateway webhook.
 * Amounts are in the same unit as Payment::$amount (system base unit).
 */
readonly class WebhookPayload
{
    public function __construct(
        /** The gateway's event type string (e.g. payment_intent.succeeded) */
        public string $event,

        /** Gateway-side payment/order identifier stored in payments.gateway_ref */
        public string $gatewayRef,

        /** Gateway transaction/charge ID stored in payments.transaction_ref */
        public ?string $transactionRef,

        /** Normalised status: paid | failed | refunded | unknown */
        public string $status,

        /** Amount in system base unit (0 when the gateway doesn't send it) */
        public int $amount,

        /** ISO 4217 currency code */
        public string $currency,

        /** Full raw payload for audit storage in gateway_response */
        public array $raw,
    ) {}
}
