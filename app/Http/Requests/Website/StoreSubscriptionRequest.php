<?php

namespace App\Http\Requests\Website;

use App\Services\Payment\GatewayFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The wallet page uses a single select whose value is either `wallet` or the
     * chosen gateway name. Normalise that into the `payment_method` + `gateway`
     * pair the controller expects.
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
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'payment_method' => ['required', Rule::in(['wallet', 'gateway'])],
            'gateway' => ['required_if:payment_method,gateway', Rule::in(app(GatewayFactory::class)->activeGateways())],
            'auto_renew' => ['sometimes', 'boolean'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.exists' => 'The selected plan is not available.',
            'gateway.in' => 'The selected payment gateway is not available.',
            'gateway.required_if' => 'Please choose a payment gateway.',
        ];
    }
}
