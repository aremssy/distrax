<x-admin-layout title="Invoice {{ $invoice->invoice_number }}">
    <x-admin-page-header title="Invoice {{ $invoice->invoice_number }}"
        :breadcrumbs="[
            ['route' => 'admin.invoices.index', 'label' => 'Invoices'],
            ['label' => $invoice->invoice_number],
        ]"
        :description="'Amount: ' . money($invoice->amount, $invoice->currency)" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-admin-card title="Invoice Details">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Invoice #</dt>
                    <dd class="min-w-0 break-all text-right font-mono font-medium text-slate-800 sm:break-normal dark:text-slate-100">{{ $invoice->invoice_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Type</dt>
                    <dd>{{ $invoice->type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                    <dd><x-admin-badge :color="$invoice->status === 'paid' ? 'green' : ($invoice->status === 'cancelled' ? 'red' : 'yellow')">{{ ucfirst($invoice->status) }}</x-admin-badge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Amount</dt>
                    <dd class="font-mono font-medium">{{ money($invoice->amount, $invoice->currency) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Issued At</dt>
                    <dd>{{ $invoice->issued_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Paid At</dt>
                    <dd>{{ $invoice->paid_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
            </dl>
        </x-admin-card>

        <x-admin-card title="User">
            <div class="text-sm">
                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->user?->name ?? '—' }}</p>
                <p class="break-all text-slate-500 sm:break-normal dark:text-slate-400">{{ $invoice->user?->email }}</p>
            </div>
        </x-admin-card>

        <x-admin-card title="Related">
            <div class="text-sm">
                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->invoiceable ? class_basename($invoice->invoiceable_type) : '—' }}</p>
                <p class="text-slate-500 dark:text-slate-400">
                    @if($invoice->invoiceable)
                        #{{ $invoice->invoiceable_id }}
                    @endif
                </p>
            </div>
        </x-admin-card>
    </div>
</x-admin-layout>
