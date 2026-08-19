<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('user:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('issued_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('issued_at', '<=', $to))
            ->latest('issued_at')
            ->paginate(25);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        return view('admin.invoices.show', [
            'invoice' => $invoice->load(['user:id,name,email', 'invoiceable']),
        ]);
    }

    public function generateFromPayment(GenerateInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $payment = Payment::with('user', 'payable')->findOrFail($data['payment_id']);

        // One invoice per payable — a subscription/package payment maps to exactly one
        // invoiceable record, so a replayed submit must not mint a second, divergent invoice.
        if ($payment->payable_type) {
            $existing = Invoice::where('invoiceable_type', $payment->payable_type)
                ->where('invoiceable_id', $payment->payable_id)
                ->first();

            if ($existing) {
                return redirect()->route('admin.invoices.show', $existing)
                    ->with('info', 'An invoice already exists for this payment.');
            }
        }

        Invoice::create([
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'user_id' => $payment->user_id,
            'invoiceable_type' => $payment->payable_type,
            'invoiceable_id' => $payment->payable_id,
            'type' => $payment->payable_type ? class_basename($payment->payable_type) : 'payment',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status === 'paid' ? 'paid' : 'pending',
            'issued_at' => now(),
            'paid_at' => $payment->paid_at,
        ]);

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice generated successfully.');
    }
}
