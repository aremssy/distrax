<?php

namespace App\Http\Requests\Api\V1\Package;

use App\Services\Payment\GatewayFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuyPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * payment_method defaults to `gateway`, so a gateway name is required unless
     * the client explicitly pays from the wallet.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_method')) {
            $this->merge(['payment_method' => 'gateway']);
        }
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['wallet', 'gateway'])],
            'gateway' => ['required_if:payment_method,gateway', Rule::in(app(GatewayFactory::class)->activeGateways())],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
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
