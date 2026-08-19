<?php

namespace App\Support\Exports\Profiles;

use App\Models\Payment;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PaymentsExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'payments';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function title(): string
    {
        return 'Payments';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'transaction_ref' => 'Transaction Ref',
            'user' => 'User',
            'gateway' => 'Gateway',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'status' => 'Status',
            'payable_type' => 'Type',
            'created_at' => 'Date',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors PaymentController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Payment::with(['user:id,name,email', 'payable'])
            ->when($request->string('gateway')->value(), fn ($query, string $gateway) => $query->where('gateway', $gateway))
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('payable_type', $type))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(function ($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                    ->orWhere('gateway_ref', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest();
    }

    /**
     * @param  Payment  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'transaction_ref' => $row->transaction_ref,
            'user' => $row->user?->name,
            'gateway' => $row->gateway,
            'amount' => $row->amount,
            'currency' => $row->currency,
            'status' => $row->status,
            'payable_type' => class_basename((string) $row->payable_type),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
