<x-admin-layout title="Booking Detail">
    <x-admin-page-header title="Booking #{{ $booking->id }}"
        :breadcrumbs="[['label' => 'Hotel Bookings', 'route' => 'admin.hotel-bookings.index'], ['label' => 'Booking #'.$booking->id]]"
        description="View and manage this hotel booking." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-admin-card title="Booking Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Listing</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->listing?->title ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Guest</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->user?->name ?? '—' }}</dd>
                        <dd class="break-all text-xs text-slate-400 sm:break-normal">{{ $booking->user?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Check In</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->check_in?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Check Out</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->check_out?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Guests</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->guests ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Amount</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ money($booking->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd>
                            <x-admin-badge :color="match($booking->status) { 'pending' => 'yellow', 'confirmed' => 'green', 'checked_in' => 'blue', 'checked_out' => 'gray', 'cancelled' => 'red', 'refunded' => 'purple', default => 'gray' }">
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </x-admin-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Refunded Amount</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->refunded_amount ? money($booking->refunded_amount) : '—' }}</dd>
                    </div>
                </dl>
            </x-admin-card>

            @if($booking->special_requests)
                <x-admin-card title="Special Requests">
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $booking->special_requests }}</p>
                </x-admin-card>
            @endif

            @if($booking->payments->count() > 0)
                <x-admin-card title="Payments">
                    <div class="divide-y divide-slate-100 dark:divide-night-800">
                        @foreach($booking->payments as $payment)
                            <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $payment->payment_method ?? '—' }}</span>
                                    <span class="text-xs text-slate-400 ml-2">{{ $payment->created_at->format('M d, Y') }}</span>
                                </div>
                                <span class="font-mono font-medium">{{ money($payment->amount) }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin-card title="Update Status">
                <form method="POST" action="{{ route('admin.hotel-bookings.update', $booking) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <x-admin.form.select name="status" label="Status">
                        @foreach(['pending','confirmed','checked_in','checked_out','cancelled','refunded'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $booking->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </x-admin.form.select>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
                    </div>
                </form>
            </x-admin-card>

            @can('payments.edit')
                <x-admin-card title="Process Refund">
                    <form method="POST" action="{{ route('admin.hotel-bookings.refund', $booking) }}" class="space-y-4"
                          data-confirm="Process refund for this booking?">
                        @csrf
                        <x-admin.form.input name="amount" label="Refund Amount" type="number" :value="old('amount', $booking->amount)" required />
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">Refund</button>
                        </div>
                    </form>
                </x-admin-card>
            @endcan
        </div>
    </div>
</x-admin-layout>
