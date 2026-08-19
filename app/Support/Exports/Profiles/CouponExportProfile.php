<?php

namespace App\Support\Exports\Profiles;

use App\Models\Coupon;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CouponExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'coupons';
    }

    public function permission(): string
    {
        return 'coupons.view';
    }

    public function title(): string
    {
        return 'Coupons';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'type' => 'Type',
            'value' => 'Value',
            'max_uses' => 'Max Uses',
            'applicable_for' => 'Applicable For',
            'is_active' => 'Active',
            'payments_count' => 'Uses',
            'starts_at' => 'Starts At',
            'expires_at' => 'Expires At',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors CouponController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Coupon::query()
            ->withCount('payments')
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where('code', 'like', "%{$search}%"))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->latest();
    }

    /**
     * @param  Coupon  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'code' => $row->code,
            'type' => $row->type,
            'value' => $row->value,
            'max_uses' => $row->max_uses,
            'applicable_for' => $row->applicable_for,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'payments_count' => $row->payments_count,
            'starts_at' => $row->starts_at?->toDateTimeString(),
            'expires_at' => $row->expires_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
