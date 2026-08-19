@extends('website.layouts.dashboard')

@section('title', __('My Jobs').' | '.config('app.name'))
@section('page-title', __('My Jobs'))
@section('page-subtitle', __('Requests customers have sent you.'))

@section('dashboard-content')
    @php
        $badges = [
            'pending' => 'bg-amber-50 text-amber-700',
            'quoted' => 'bg-indigo-50 text-indigo-700',
            'accepted' => 'bg-sky-50 text-sky-700',
            'in_progress' => 'bg-violet-50 text-violet-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        {{-- Status filter --}}
        <nav class="-mx-4 flex snap-x gap-2 overflow-x-auto px-4 pb-1" aria-label="{{ __('Filter jobs by status') }}">
            <a href="{{ route('technician.bookings.index') }}"
                class="shrink-0 snap-start rounded-lg px-3.5 py-2 text-sm font-medium transition {{ $statusFilter === null ? 'bg-indigo-50 font-semibold text-indigo-600' : 'text-slate-600 hover:bg-white' }}">
                {{ __('All') }}
            </a>
            @foreach ($statuses as $status)
                <a href="{{ route('technician.bookings.index', ['status' => $status]) }}"
                    class="shrink-0 snap-start whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition {{ $statusFilter === $status ? 'bg-indigo-50 font-semibold text-indigo-600' : 'text-slate-600 hover:bg-white' }}">
                    {{ __(ucfirst(str_replace('_', ' ', $status))) }}
                    <span class="ms-1 text-xs text-slate-400">{{ $statusCounts[$status] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Job requests') }}">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">{{ __('Customer') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('Job') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('Scheduled') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($bookings as $booking)
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="font-medium text-slate-950">{{ $booking->user?->name ?? __('Customer') }}</span>
                                    @if ($booking->is_urgent)
                                        <span class="ms-1.5 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ __('Urgent') }}</span>
                                    @endif
                                </td>
                                <td class="min-w-60 max-w-80 truncate px-5 py-3 text-slate-600">{{ $booking->description }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $booking->scheduled_at?->format('d M Y, H:i') ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badges[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ __(ucfirst(str_replace('_', ' ', $booking->status))) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-end">
                                    <a href="{{ route('technician.bookings.show', $booking) }}"
                                        class="font-semibold text-indigo-600 hover:text-indigo-700">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-8 text-center text-slate-500" colspan="5">
                                    {{ $statusFilter ? __('No jobs with this status. Requests from customers will appear here.') : __('No jobs yet. Requests from customers will appear here.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $bookings->links() }}
    </div>
@endsection
