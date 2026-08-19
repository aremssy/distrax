<?php

namespace App\Http\Requests\Api\V1\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'integer', 'min:1'],
            'context' => ['sometimes', 'nullable', Rule::in(['subscription', 'package', 'technician_booking', 'hotel_booking'])],
        ];
    }
}
