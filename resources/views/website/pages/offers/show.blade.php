@extends('website.layouts.master')

@section('title', __('Offer').' | '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('offers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back to my offers') }}
        </a>

        @if (session('success'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-950">{{ $offer->listing->title }}</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $offer->listing->address }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide
                        @if ($offer->status === 'accepted') bg-emerald-100 text-emerald-700
                        @elseif ($offer->status === 'rejected' || $offer->status === 'expired' || $offer->status === 'withdrawn') bg-slate-100 text-slate-600
                        @else bg-indigo-100 text-indigo-700 @endif">
                        {{ __(ucfirst($offer->status)) }}
                    </span>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Current amount') }}</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-950">{{ money($offer->amount, $offer->currency_code) }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Buyer') }}</dt>
                        <dd class="mt-1 truncate text-lg font-bold text-slate-950">{{ $offer->buyer->name }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expires') }}</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-950">{{ $offer->expires_at?->format('d M Y') ?? __('No expiry') }}</dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-wrap gap-2">
                    @if (in_array($offer->status, ['pending', 'countered'], true))
                        @if ($isSeller)
                            <button type="button" data-modal="counter"
                                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                <i class="h-4 w-4" data-lucide="refresh-cw" aria-hidden="true"></i>{{ __('Counter') }}
                            </button>
                            <form method="POST" action="{{ route('offers.accept', $offer) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <i class="h-4 w-4" data-lucide="check" aria-hidden="true"></i>{{ __('Accept') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('offers.reject', $offer) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                                    <i class="h-4 w-4" data-lucide="x" aria-hidden="true"></i>{{ __('Reject') }}
                                </button>
                            </form>
                        @else
                            <button type="button" data-modal="counter"
                                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                <i class="h-4 w-4" data-lucide="refresh-cw" aria-hidden="true"></i>{{ __('Counter') }}
                            </button>
                            <form method="POST" action="{{ route('offers.withdraw', $offer) }}" class="inline"
                                onsubmit="return confirm('{{ __('Withdraw this offer?') }}')">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-rose-500 hover:text-rose-600">
                                    <i class="h-4 w-4" data-lucide="minus-circle" aria-hidden="true"></i>{{ __('Withdraw') }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-bold text-slate-950">{{ __('Negotiation history') }}</h2>
            </div>
            <div class="space-y-4 p-6">
                @forelse ($offer->negotiations as $negotiation)
                    <div class="flex gap-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                            {{ strtoupper(substr($negotiation->sender->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $negotiation->sender->name }}</span>
                                <span class="text-xs text-slate-400">{{ $negotiation->created_at->diffForHumans() }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ money($negotiation->amount, $offer->currency_code) }}</span>
                            </div>
                            @if ($negotiation->message)
                                <p class="mt-1 text-sm text-slate-700">{{ $negotiation->message }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No negotiation messages yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="counter-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" x-cloak>
        <form method="POST" action="{{ route('offers.counter', $offer) }}" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf
            <h3 class="text-lg font-bold text-slate-950">{{ __('Submit a counter offer') }}</h3>
            <label for="counter-amount" class="mt-4 block text-sm font-semibold text-slate-800">{{ __('New amount') }}</label>
            <input id="counter-amount" name="amount" type="number" min="1" step="1" required value="{{ $offer->amount }}"
                class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
            <label for="counter-message" class="mt-4 block text-sm font-semibold text-slate-800">{{ __('Message') }}</label>
            <textarea id="counter-message" name="message" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"></textarea>
            <div class="mt-5 flex items-center justify-end gap-3">
                <button type="button" class="modal-close rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">{{ __('Cancel') }}</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Send counter') }}</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-modal="counter"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById('counter-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });
        document.querySelectorAll('.modal-close').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById('counter-modal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        });
    </script>
@endpush

