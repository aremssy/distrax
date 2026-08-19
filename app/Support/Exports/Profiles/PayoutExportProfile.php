<?php

namespace App\Support\Exports\Profiles;

use App\Models\Payout;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PayoutExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'payouts';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function title(): string
    {
        return 'Payouts';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'user' => 'User',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'gateway' => 'Gateway',
            'account_ref' => 'Account Ref',
            'status' => 'Status',
            'processed_by' => 'Processed By',
            'processed_at' => 'Processed At',
            'created_at' => 'Date',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors PayoutController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Payout::with(['user:id,name,email', 'processedBy:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('gateway')->value(), fn ($query, string $gateway) => $query->where('gateway', $gateway))
            ->latest();
    }

    /**
     * @param  Payout  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'user' => $row->user?->name,
            'amount' => $row->amount,
            'currency' => $row->currency,
            'gateway' => $row->gateway,
            'account_ref' => $row->account_ref,
            'status' => $row->status,
            'processed_by' => $row->processedBy?->name,
            'processed_at' => $row->processed_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
