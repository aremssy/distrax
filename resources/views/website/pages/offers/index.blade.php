@extends('website.layouts.dashboard')

@section('title', __('My Offers').' | '.config('app.name'))
@section('page-title', __('My Offers'))

@section('dashboard-content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-950">{{ __('Offers') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Track and manage offers you have made or received.') }}</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Property') }}</th>
                            <th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Amount') }}</th>
                            <th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Direction') }}</th>
                            <th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Updated') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($offers as $offer)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-4">
                                    <a href="{{ route('offers.show', $offer) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ $offer->listing->title }}</a>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ money($offer->amount, $offer->currency_code) }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if ($offer->buyer_id === auth()->id())
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600"><i class="h-3.5 w-3.5" data-lucide="arrow-up-right"></i>{{ __('You made') }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600"><i class="h-3.5 w-3.5" data-lucide="arrow-down-left"></i>{{ __('You received') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide
                                        @if ($offer->status === 'accepted') bg-emerald-100 text-emerald-700
                                        @elseif (in_array($offer->status, ['rejected', 'expired', 'withdrawn'], true)) bg-slate-100 text-slate-600
                                        @else bg-indigo-100 text-indigo-700 @endif">
                                        {{ __(ucfirst($offer->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $offer->updated_at->diffForHumans() }}</td>
                                <td class="px-5 py-4 text-end">
                                    <a href="{{ route('offers.show', $offer) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                        {{ __('View') }}<i class="h-4 w-4" data-lucide="chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">{{ __('No offers yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $offers->links() }}
        </div>
    </div>
@endsection
