<?php

namespace App\Http\Requests\Website;

use App\Services\Payment\GatewayFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The booking widget posts a single `payment_method` select whose value is
     * either "wallet" or a gateway key (e.g. "stripe") — split that back into
     * `payment_method`/`gateway` here, mirroring StoreSubscriptionRequest.
     */
    protected function prepareForValidation(): void
    {
        $method = (string) $this->input('payment_method', 'wallet');

        if ($method !== 'wallet' && $method !== 'gateway') {
            $this->merge(['payment_method' => 'gateway', 'gateway' => $method]);
        }
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:property_listings,id'],
            'check_in' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['wallet', 'gateway'])],
            'gateway' => ['required_if:payment_method,gateway', Rule::in(app(GatewayFactory::class)->activeGateways())],
        ];
    }

    public function messages(): array
    {
        return [
            'gateway.in' => 'The selected payment gateway is not available.',
            'gateway.required_if' => 'Please choose a payment gateway.',
        ];
    }
}
