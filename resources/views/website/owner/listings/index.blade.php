@extends('website.layouts.dashboard')

@section('title', __('My Listings').' | '.config('app.name'))
@section('page-title', __('My Listings'))

@section('dashboard-content')
    @php
        $statusStyles = [
            'draft' => 'bg-slate-100 text-slate-700',
            'pending' => 'bg-amber-50 text-amber-700',
            'active' => 'bg-emerald-50 text-emerald-700',
            'rented' => 'bg-indigo-50 text-indigo-700',
            'sold' => 'bg-indigo-50 text-indigo-700',
            'archived' => 'bg-slate-100 text-slate-600',
            'rejected' => 'bg-rose-50 text-rose-700',
        ];
    @endphp

    <div class="space-y-6" data-purpose="owner-listings">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div class="grid min-w-0 flex-1 grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                @foreach ([
                    [__('Listings'), number_format($statusCounts->sum()), 'building-2'],
                    [__('Views'), number_format($analytics['total_views']), 'eye'],
                    [__('Leads'), number_format($analytics['total_leads']), 'trending-up'],
                    [__('Conversion'), $analytics['conversion_rate'].'%', 'percent'],
                    [__('Remaining'), $summary['remaining_posts'] ?? '∞', 'ticket'],
                ] as [$label, $value, $icon])
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="flex items-center gap-1.5 text-xs text-slate-500">
                            <i class="h-3.5 w-3.5 text-slate-500" data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ $label }}
                        </p>
                        <p class="mt-1 text-xl font-bold text-slate-950">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <a class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                href="{{ route('owner.listings.create') }}">
                <i class="h-4 w-4" data-lucide="plus" aria-hidden="true"></i>{{ __('Add Property') }}
            </a>
        </div>

        <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="{{ __('Filter listings by status') }}">
            <a href="{{ route('owner.listings.index') }}"
                class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ $statusFilter === null ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-white' }}">
                {{ __('All (:count)', ['count' => $statusCounts->sum()]) }}
            </a>
            @foreach ($statuses as $status)
                @continue(($statusCounts[$status] ?? 0) === 0)
                <a href="{{ route('owner.listings.index', ['status' => $status]) }}"
                    class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-semibold capitalize transition {{ $statusFilter === $status ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-white' }}">
                    {{ __(ucfirst($status)) }} ({{ $statusCounts[$status] }})
                </a>
            @endforeach
        </nav>

        <div class="space-y-3">
            @forelse ($listings as $listing)
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-slate-950">{{ $listing->title }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusStyles[$listing->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ __(ucfirst($listing->status)) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $listing->zone?->name }} · {{ moneyFrom($listing->price, $listing->currency_code) }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ __(':views views · :favorites favorites · :visits visit requests · :reveals contact reveals · :inquiries inquiries', [
                                    'views' => number_format($listing->view_count),
                                    'favorites' => $listing->favorites_count,
                                    'visits' => $listing->visit_schedules_count,
                                    'reveals' => $listing->contact_reveals_count,
                                    'inquiries' => $listing->conversations_count,
                                ]) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600"
                                href="{{ route('owner.listings.edit', $listing) }}">
                                {{ $listing->status === 'draft' ? __('Continue draft') : __('Edit') }}
                            </a>

                            @if (in_array('multi_unit', $summary['unlocked_features'] ?? [], true))
                                <a class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600"
                                    href="{{ route('owner.rent-management.units.index', $listing) }}">{{ __('Units') }}</a>
                            @endif

                            @if ($listing->status === 'active')
                                <a class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600"
                                    href="{{ route('properties.show', $listing) }}">{{ __('View') }}</a>

                                @if ($boostPackage && ! $listing->is_featured)
                                    <form action="{{ route('listing-packages.store') }}" method="POST"
                                        data-confirm="{{ __('Boost ":title" for :price using your wallet balance?', ['title' => addslashes($listing->title), 'price' => money($boostPackage->price)]) }}">
                                        @csrf
                                        <input type="hidden" name="package_id" value="{{ $boostPackage->id }}">
                                        <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                                        <input type="hidden" name="payment_method" value="wallet">
                                        <button class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-300">
                                            {{ __('Boost (:price)', ['price' => money($boostPackage->price)]) }}
                                        </button>
                                    </form>
                                @elseif ($listing->is_featured)
                                    <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">{{ __('Boosted') }}</span>
                                @endif
                                <form action="{{ route('owner.listings.freshness', $listing) }}" method="POST">
                                    @csrf
                                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">{{ __('Still available') }}</button>
                                </form>
                                @foreach (['rented', 'sold', 'archived'] as $status)
                                    <form action="{{ route('owner.listings.status', $listing) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold capitalize text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">{{ __('Mark :status', ['status' => $status]) }}</button>
                                    </form>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <h2 class="font-bold text-slate-950">
                        {{ $statusFilter ? __('No :status listings', ['status' => $statusFilter]) : __('You have no listings yet') }}
                    </h2>
                    <a class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                        href="{{ route('owner.listings.create') }}">{{ __('Post your first property') }}</a>
                </div>
            @endforelse
        </div>

        {{ $listings->links() }}
    </div>
@endsection
