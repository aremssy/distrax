<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string', Rule::in(['stripe', 'paypal', 'bkash', 'razorpay', 'paystack'])],
        ];
    }
}
