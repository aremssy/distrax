@extends('website.layouts.dashboard')

@section('title', __('Job Details').' | '.config('app.name'))
@section('page-title', __('Job Details'))
@section('page-subtitle', __('Review the request, send a quote, and keep the customer updated.'))

@section('dashboard-content')
    @php
        $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10';
        $labelClass = 'block text-sm font-semibold text-slate-800';

        $badges = [
            'pending' => 'bg-amber-50 text-amber-700',
            'quoted' => 'bg-indigo-50 text-indigo-700',
            'accepted' => 'bg-sky-50 text-sky-700',
            'in_progress' => 'bg-violet-50 text-violet-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
        ];

        // Mirrors the transitions the controller allows.
        $nextActions = match ($booking->status) {
            'accepted' => [['in_progress', __('Start Job'), 'play'], ['cancelled', __('Decline'), 'x']],
            'in_progress' => [['completed', __('Mark Completed'), 'check'], ['cancelled', __('Decline'), 'x']],
            'pending', 'quoted' => [['cancelled', __('Decline'), 'x']],
            default => [],
        };
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ route('technician.bookings.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-950">
            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back to jobs') }}
        </a>

        {{-- Request --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Request details') }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <h2 class="font-bold text-slate-950">{{ __('Request') }}</h2>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badges[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ __(ucfirst(str_replace('_', ' ', $booking->status))) }}
                </span>
            </div>

            <dl class="grid gap-5 p-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Customer') }}</dt>
                    <dd class="mt-1 font-medium text-slate-950">{{ $booking->user?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Phone') }}</dt>
                    <dd class="mt-1 font-medium text-slate-950">{{ $booking->user?->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Scheduled for') }}</dt>
                    <dd class="mt-1 font-medium text-slate-950">{{ $booking->scheduled_at?->format('d M Y, H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Agreed amount') }}</dt>
                    <dd class="mt-1 font-medium text-slate-950">{{ $booking->agreed_amount ? money($booking->agreed_amount, $booking->currency_code) : '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">{{ __('Address') }}</dt>
                    <dd class="mt-1 font-medium text-slate-950">{{ $booking->address ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-slate-500">{{ __('What they need') }}</dt>
                    <dd class="mt-1 whitespace-pre-line text-slate-700">{{ $booking->description }}</dd>
                </div>
            </dl>

            @if ($nextActions)
                <div class="flex flex-wrap gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    @foreach ($nextActions as [$status, $label, $icon])
                        <form method="POST" action="{{ route('technician.bookings.status', $booking) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $status === 'cancelled' ? 'border border-slate-200 text-slate-700 hover:border-rose-300 hover:text-rose-600' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                                <i class="h-4 w-4" data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Quote form: only meaningful while the job is still pending --}}
        @if ($booking->status === 'pending')
            <form method="POST" action="{{ route('technician.bookings.quote.store', $booking) }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                @csrf
                <h2 class="border-b border-slate-100 px-6 py-4 font-bold text-slate-950">{{ __('Send a quote') }}</h2>

                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="amount">{{ __('Amount') }}</label>
                        <input id="amount" class="mt-2 {{ $inputClass }}" name="amount" type="number" min="1"
                            value="{{ old('amount') }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="valid_until">{{ __('Valid until') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <input id="valid_until" class="mt-2 {{ $inputClass }}" name="valid_until" type="datetime-local"
                            value="{{ old('valid_until') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}" for="description">{{ __('Notes') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <textarea id="description" class="mt-2 {{ $inputClass }}" name="description" rows="4" maxlength="2000"
                            placeholder="{{ __('What the price covers, parts needed, expected duration.') }}">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>{{ __('Send Quote') }}
                    </button>
                </div>
            </form>
        @endif

        {{-- Quote history --}}
        @if ($booking->quotes->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Quotes sent') }}">
                <h2 class="border-b border-slate-100 px-6 py-4 font-bold text-slate-950">{{ __('Quotes sent') }}</h2>
                <div class="divide-y">
                    @foreach ($booking->quotes as $quote)
                        <div class="flex flex-wrap items-start justify-between gap-3 px-6 py-4 text-sm">
                            <div>
                                <p class="font-semibold text-slate-950">{{ money($quote->amount, $booking->currency_code) }}</p>
                                @if ($quote->description)
                                    <p class="mt-1 text-slate-600">{{ $quote->description }}</p>
                                @endif
                                @if ($quote->valid_until)
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Valid until :date', ['date' => $quote->valid_until->format('d M Y, H:i')]) }}</p>
                                @endif
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badges[$quote->status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ __(ucfirst($quote->status)) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
