<?php

namespace App\Support\Exports\Profiles;

use App\Models\Refund;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RefundExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'refunds';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function title(): string
    {
        return 'Refunds';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'payment_id' => 'Payment ID',
            'amount' => 'Amount',
            'reason' => 'Reason',
            'status' => 'Status',
            'requested_by' => 'Requested By',
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
     * Mirrors PaymentController@refundsIndex filters.
     */
    public function query(Request $request): Builder
    {
        return Refund::with(['payment:id,amount,currency', 'requestedBy:id,name,email', 'processedBy:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest();
    }

    /**
     * @param  Refund  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'payment_id' => $row->payment_id,
            'amount' => $row->amount,
            'reason' => $row->reason,
            'status' => $row->status,
            'requested_by' => $row->requestedBy?->name,
            'processed_by' => $row->processedBy?->name,
            'processed_at' => $row->processed_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
