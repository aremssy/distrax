<x-admin-layout title="Booking Detail">
    <x-admin-page-header title="Booking #{{ $booking->id }}"
        :breadcrumbs="[['label' => 'Technician Bookings', 'route' => 'admin.tech-bookings.index'], ['label' => 'Booking #'.$booking->id]]"
        description="View and manage this technician booking." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-admin-card title="Booking Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Customer</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->user?->name ?? '—' }}</dd>
                        <dd class="break-all text-xs text-slate-400 sm:break-normal">{{ $booking->user?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Technician</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->technician?->user?->name ?? '—' }}</dd>
                        <dd class="text-xs text-slate-400">{{ $booking->technician?->category?->name ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Scheduled At</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->scheduled_at ? $booking->scheduled_at->format('M d, Y h:i A') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Agreed Amount</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $booking->agreed_amount ? money($booking->agreed_amount) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd>
                            <x-admin-badge :color="match($booking->status) { 'pending' => 'yellow', 'quoted' => 'blue', 'accepted' => 'indigo', 'in_progress' => 'purple', 'completed' => 'green', 'cancelled' => 'red', default => 'gray' }">
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </x-admin-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Urgent</dt>
                        <dd>
                            <x-admin-badge :color="$booking->is_urgent ? 'red' : 'gray'">{{ $booking->is_urgent ? 'Yes' : 'No' }}</x-admin-badge>
                        </dd>
                    </div>
                </dl>
            </x-admin-card>

            <x-admin-card title="Description">
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ $booking->description ?? 'No description provided.' }}</p>
            </x-admin-card>

            @if($booking->quotes->count() > 0)
                <x-admin-card title="Quotes">
                    <div class="divide-y divide-slate-100 dark:divide-night-800">
                        @foreach($booking->quotes as $quote)
                            <div class="py-3 first:pt-0 last:pb-0">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="min-w-0 truncate font-medium text-slate-700 dark:text-slate-300">{{ $quote->technician?->user?->name ?? '—' }}</span>
                                    <span class="shrink-0 font-mono font-medium text-slate-800 dark:text-slate-100">{{ money($quote->amount) }}</span>
                                </div>
                                @if($quote->description)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $quote->description }}</p>
                                @endif
                                @if($quote->valid_until)
                                    <p class="text-xs text-slate-400 mt-1">Valid until {{ $quote->valid_until->format('M d, Y') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin-card title="Update Status">
                <form method="POST" action="{{ route('admin.tech-bookings.update', $booking) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <x-admin.form.select name="status" label="Status">
                        @foreach(['pending','quoted','accepted','in_progress','completed','cancelled'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $booking->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </x-admin.form.select>
                    <x-admin.form.input name="agreed_amount" label="Agreed Amount" type="number" :value="old('agreed_amount', $booking->agreed_amount)" />
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
                    </div>
                </form>
            </x-admin-card>

            @can('technician_bookings.edit')
                <x-admin-card title="Add Quote">
                    <form method="POST" action="{{ route('admin.tech-bookings.quotes.store', $booking) }}" class="space-y-4">
                        @csrf
                        <x-admin.form.input name="technician_id" label="Technician ID" type="number" required />
                        <x-admin.form.input name="amount" label="Amount" type="number" required />
                        <x-admin.form.textarea name="description" label="Description" rows="2" />
                        <x-admin.form.input name="valid_until" label="Valid Until" type="date" />
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Add Quote</button>
                        </div>
                    </form>
                </x-admin-card>
            @endcan
        </div>
    </div>
</x-admin-layout>
