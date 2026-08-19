<?php

namespace App\Support\Exports\Profiles;

use App\Models\Invoice;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InvoiceExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'invoices';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function title(): string
    {
        return 'Invoices';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'invoice_number' => 'Invoice Number',
            'user' => 'User',
            'type' => 'Type',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'status' => 'Status',
            'issued_at' => 'Issued At',
            'paid_at' => 'Paid At',
            'created_at' => 'Date',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors InvoiceController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Invoice::with('user:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('issued_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('issued_at', '<=', $to))
            ->latest('issued_at');
    }

    /**
     * @param  Invoice  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'invoice_number' => $row->invoice_number,
            'user' => $row->user?->name,
            'type' => $row->type,
            'amount' => $row->amount,
            'currency' => $row->currency,
            'status' => $row->status,
            'issued_at' => $row->issued_at?->toDateTimeString(),
            'paid_at' => $row->paid_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
