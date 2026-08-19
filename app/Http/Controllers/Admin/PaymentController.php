<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentCsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['user:id,name,email', 'payable'])
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
            ->latest()
            ->paginate(25);

        return view('admin.payments.index', [
            'payments' => $payments,
            'gateways' => Payment::distinct()->pluck('gateway'),
            'statuses' => ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'],
            'types' => Payment::distinct()->pluck('payable_type'),
        ]);
    }

    public function show(Payment $payment)
    {
        return view('admin.payments.show', [
            'payment' => $payment->load(['user:id,name,email', 'payable', 'coupon', 'refunds.requestedBy:id,name,email', 'refunds.processedBy:id,name,email']),
        ]);
    }

    public function exportCsv(Request $request, PaymentCsvExporter $exporter): StreamedResponse
    {
        return $exporter->export($request);
    }

    public function refundsIndex(Request $request)
    {
        $refunds = Refund::with(['payment:id,amount,currency', 'requestedBy:id,name,email', 'processedBy:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function processRefund(RefundPaymentRequest $request, Refund $refund): RedirectResponse
    {
        $data = $request->validated();

        // Processed and rejected are terminal — a replayed form must not flip
        // them back (or re-process a refund that already adjusted the payment).
        if (in_array($refund->status, ['processed', 'rejected'], true)) {
            return back()->with('error', "This refund has already been {$refund->status} and can no longer be changed.");
        }

        // Serialize with any other refund activity on the same payment (the
        // customer-facing RefundService uses the same lock key).
        $lock = Cache::lock("refund:payment:{$refund->payment_id}", 15);

        if (! $lock->get()) {
            return back()->with('error', 'Another refund for this payment is being processed. Try again in a moment.');
        }

        try {
            if ($data['status'] === 'processed') {
                $payment = $refund->payment;
                $alreadyRefunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
                $maxRefundable = $payment->amount - $alreadyRefunded;

                if ($refund->amount > $maxRefundable) {
                    return back()->with('error', "This refund ({$refund->amount}) exceeds the remaining refundable amount ({$maxRefundable}).");
                }
            }

            DB::transaction(function () use ($data, $refund): void {
                $refund->update([
                    'status' => $data['status'],
                    'admin_note' => $data['admin_note'] ?? $refund->admin_note,
                    'processed_by' => auth()->id(),
                    'processed_at' => $data['status'] === 'processed' ? now() : $refund->processed_at,
                ]);

                if ($data['status'] === 'processed') {
                    $payment = $refund->payment;
                    $totalRefunded = $payment->refunds()->where('status', 'processed')->sum('amount');
                    $payment->update([
                        'status' => $totalRefunded >= $payment->amount ? 'refunded' : 'partially_refunded',
                    ]);
                }
            });
        } finally {
            $lock->release();
        }

        return redirect()->route('admin.refunds.index')->with('success', 'Refund updated successfully.');
    }
}
