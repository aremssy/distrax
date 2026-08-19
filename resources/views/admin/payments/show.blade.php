<x-admin-layout title="Payment #{{ $payment->id }}">
    <x-admin-page-header title="Payment #{{ $payment->id }}"
        :breadcrumbs="[
            ['route' => 'admin.payments.index', 'label' => 'Payments'],
            ['label' => 'Payment #' . $payment->id],
        ]"
        :description="'Amount: ' . money($payment->amount, $payment->currency)" />

    {{-- Payment Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-admin-card title="Payment Info">
            <dl class="space-y-3 text-sm">
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                    <dd><x-admin-badge :color="$payment->status === 'paid' ? 'green' : ($payment->status === 'failed' ? 'red' : ($payment->status === 'pending' ? 'yellow' : 'gray'))">{{ ucwords(str_replace('_', ' ', $payment->status)) }}</x-admin-badge></dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Amount</dt>
                    <dd class="font-mono font-medium">{{ money($payment->amount, $payment->currency) }}</dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Gateway Fee</dt>
                    <dd class="font-mono">{{ $payment->gateway_fee ? money($payment->gateway_fee, $payment->currency) : '—' }}</dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Gateway</dt>
                    <dd>{{ ucfirst($payment->gateway) }}</dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Transaction Ref</dt>
                    <dd class="min-w-0 break-all text-right font-mono text-xs sm:break-normal">{{ $payment->transaction_ref ?? '—' }}</dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Gateway Ref</dt>
                    <dd class="min-w-0 break-all text-right font-mono text-xs sm:break-normal">{{ $payment->gateway_ref ?? '—' }}</dd>
                </div>
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Created</dt>
                    <dd>{{ $payment->created_at->format('M j, Y g:i A') }}</dd>
                </div>
                @if($payment->paid_at)
                <div class="flex min-w-0 justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Paid At</dt>
                    <dd>{{ $payment->paid_at->format('M j, Y g:i A') }}</dd>
                </div>
                @endif
            </dl>
        </x-admin-card>

        <x-admin-card title="User">
            <div class="text-sm">
                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $payment->user?->name ?? '—' }}</p>
                <p class="break-all text-slate-500 sm:break-normal dark:text-slate-400">{{ $payment->user?->email }}</p>
            </div>
        </x-admin-card>

        <x-admin-card title="Payable">
            <div class="text-sm">
                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $payment->payable ? class_basename($payment->payable_type) : '—' }}</p>
                <p class="text-slate-500 dark:text-slate-400">
                    @if($payment->payable)
                        #{{ $payment->payable_id }}
                        @if(method_exists($payment->payable, 'getName'))
                            — {{ $payment->payable->getName() }}
                        @endif
                    @endif
                </p>
            </div>
        </x-admin-card>
    </div>

    {{-- Coupon Info --}}
    @if($payment->coupon)
    <x-admin-card title="Applied Coupon" class="mb-6">
        <div class="text-sm">
            <p class="font-medium text-slate-700 dark:text-slate-300">{{ $payment->coupon->code }}</p>
            <p class="text-slate-500 dark:text-slate-400">
                Discount: {{ $payment->discount_amount ? money($payment->discount_amount, $payment->currency) : 'N/A' }}
            </p>
        </div>
    </x-admin-card>
    @endif

    {{-- Refunds --}}
    <x-admin-card title="Refunds">
        @if($payment->refunds->isNotEmpty())
            <div class="overflow-x-auto -mx-5 -mb-5">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Amount</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reason</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Requested By</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Processed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                        @foreach($payment->refunds as $refund)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                                <td class="px-5 py-2.5 text-xs text-slate-400 font-mono">{{ $refund->id }}</td>
                                <td class="px-5 py-2.5 font-mono font-medium text-slate-700 dark:text-slate-200">{{ money($refund->amount, $payment->currency) }}</td>
                                <td class="px-5 py-2.5 text-xs text-slate-600 dark:text-slate-400 max-w-48 line-clamp-2">{{ $refund->reason }}</td>
                                <td class="px-5 py-2.5 whitespace-nowrap">
                                    <x-admin-badge :color="$refund->status === 'processed' ? 'green' : ($refund->status === 'approved' ? 'blue' : ($refund->status === 'rejected' ? 'red' : 'yellow'))">
                                        {{ ucfirst($refund->status) }}
                                    </x-admin-badge>
                                </td>
                                <td class="px-5 py-2.5 text-xs text-slate-600 dark:text-slate-400">{{ $refund->requestedBy?->name ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $refund->processed_at?->format('M j, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No refunds for this payment.</p>
        @endif
    </x-admin-card>
</x-admin-layout>
