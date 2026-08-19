<?php

namespace App\Http\Requests\Website;

use App\Services\Payment\GatewayFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchasePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the single `wallet`-or-gateway-name select into the
     * `payment_method` + `gateway` pair the controller expects.
     */
    protected function prepareForValidation(): void
    {
        $method = (string) $this->input('payment_method', 'wallet');

        if ($method !== 'wallet' && $method !== 'gateway') {
            $this->merge(['payment_method' => 'gateway', 'gateway' => $method]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists('listing_packages', 'id')->where('is_active', true)],
            'payment_method' => ['required', Rule::in(['wallet', 'gateway'])],
            'gateway' => ['required_if:payment_method,gateway', Rule::in(app(GatewayFactory::class)->activeGateways())],
            // Only meaningful for a "boost" package; the controller checks ownership.
            'listing_id' => ['sometimes', 'nullable', 'integer', Rule::exists('property_listings', 'id')],
        ];
    }
}
