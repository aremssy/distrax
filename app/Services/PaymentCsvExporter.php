<?php

namespace App\Services;

use App\Models\Payment;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentCsvExporter
{
    public function __construct(private readonly CsvStreamExporter $exporter) {}

    /**
     * Stream the payments matching the request filters as a CSV download,
     * chunked to keep memory flat.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Payment::with('user:id,name,email')
            ->when($request->string('gateway')->value(), fn ($query, string $gateway) => $query->where('gateway', $gateway))
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest();

        $columns = ['ID', 'User', 'Email', 'Gateway', 'Amount', 'Currency', 'Status', 'Transaction Ref', 'Paid At', 'Created At'];

        return $this->exporter->stream('payments.csv', $columns, function (Closure $write) use ($query): void {
            $query->chunk(500, function (Collection $payments) use ($write): void {
                foreach ($payments as $payment) {
                    $write([
                        $payment->id,
                        $payment->user->name,
                        $payment->user->email,
                        $payment->gateway,
                        $payment->amount,
                        $payment->currency,
                        $payment->status,
                        $payment->transaction_ref,
                        $payment->paid_at?->format('Y-m-d H:i:s'),
                        $payment->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }
}
