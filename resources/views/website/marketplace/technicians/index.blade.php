@extends('website.layouts.master')

@section('title', __('Find Technicians').' | '.config('app.name'))

@section('meta_description', __('Browse verified local technicians for repairs, cleaning, plumbing and more. Compare ratings, experience and hourly rates.'))

@section('content')
    <main class="min-h-screen bg-slate-50 py-8 sm:py-10" data-purpose="technicians-index-page">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <section class="flex items-start justify-between gap-8">
                <div class="max-w-lg">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950">{{ __('Hire a trusted technician') }}</h1>
                    <p class="mt-2 text-base text-slate-500">{{ __('Verified local professionals available in your area.') }}</p>
                </div>

                <div class="hidden h-28 w-80 shrink-0 lg:block" aria-hidden="true" data-purpose="technicians-hero-art">
                    @include('website.marketplace.technicians._hero-art')
                </div>
            </section>

            <form action="{{ route('technicians.index') }}" method="GET" role="search" data-purpose="technicians-search" data-live-search="#technician-results"
                class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 lg:grid-cols-3">
                    <div class="relative flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                        <i class="h-5 w-5 shrink-0 text-slate-400" data-lucide="layout-grid" aria-hidden="true"></i>
                        <label class="sr-only" for="technician-category">{{ __('Category') }}</label>
                        <select id="technician-category" name="category_id"
                            class="h-12 min-w-0 flex-1 appearance-none border-0 bg-transparent pe-6 text-sm text-slate-700 focus:outline-none focus:ring-0">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <i class="pointer-events-none absolute inset-e-4 h-4 w-4 text-slate-400" data-lucide="chevron-down" aria-hidden="true"></i>
                    </div>

                    <div class="relative flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                        <i class="h-5 w-5 shrink-0 text-slate-400" data-lucide="map-pin" aria-hidden="true"></i>
                        <label class="sr-only" for="technician-zone">{{ __('Area') }}</label>
                        <select id="technician-zone" name="zone_id"
                            class="h-12 min-w-0 flex-1 appearance-none border-0 bg-transparent pe-6 text-sm text-slate-700 focus:outline-none focus:ring-0">
                            <option value="">{{ __('All areas') }}</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <i class="pointer-events-none absolute inset-e-4 h-4 w-4 text-slate-400" data-lucide="chevron-down" aria-hidden="true"></i>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                            class="inline-flex h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="h-4 w-4" data-lucide="search" aria-hidden="true"></i>
                            {{ __('Find technicians') }}
                        </button>
                        @if ($hasFilters)
                            <a href="{{ route('technicians.index') }}"
                                class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 transition hover:border-indigo-500 hover:text-indigo-600">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div id="technician-results" class="transition-opacity duration-200">
                @include('website.marketplace.technicians._results')
            </div>

            @include('website.marketplace.technicians._assurances')
        </div>
    </main>
@endsection
