<?php

namespace App\Http\Requests\Api\V1\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy gate checked in controller
    }

    public function rules(): array
    {
        return [
            // Owners can only mark as rented, sold, or archived.
            // Admin-controlled statuses (pending, active, rejected) are excluded.
            'status' => ['required', Rule::in(['rented', 'sold', 'archived'])],
        ];
    }
}
