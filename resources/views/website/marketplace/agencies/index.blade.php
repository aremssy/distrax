@extends('website.layouts.master')

@section('title', __('Real Estate Agencies').' | '.config('app.name'))

@section('content')
    <main class="min-h-screen bg-slate-50 py-10 sm:py-14" data-purpose="agencies-index-page">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <nav aria-label="Breadcrumb" class="mb-6">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <li><a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li class="font-semibold text-slate-950" aria-current="page">{{ __('Agencies') }}</li>
                </ol>
            </nav>

            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">{{ __('Our Agencies') }}</h1>
                <p class="mt-3 max-w-lg text-base text-slate-600">
                    {{ __('Browse established property agencies and find a team you can trust to buy, sell or rent with.') }}
                </p>
            </div>

            <form action="{{ route('agencies.index') }}" method="GET" role="search" data-purpose="agencies-search" data-live-search="#agency-results"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex min-w-0 flex-1 items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                        <i class="h-5 w-5 shrink-0 text-slate-500" data-lucide="search" aria-hidden="true"></i>
                        <label class="sr-only" for="agency-search">{{ __('Search agencies') }}</label>
                        <input id="agency-search" type="search" name="q" value="{{ $search }}"
                            placeholder="{{ __('Search by agency name or location...') }}"
                            class="h-12 min-w-0 flex-1 border-0 bg-transparent text-base text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                    </div>

                    <label class="flex h-12 shrink-0 cursor-pointer items-center gap-2.5 rounded-xl border px-4 text-sm font-semibold transition {{ $verifiedOnly ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                        <input type="checkbox" name="verified" value="1" @checked($verifiedOnly)
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        {{ __('Verified only') }}
                    </label>

                    <button type="submit"
                        class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-7 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        <i class="h-4 w-4" data-lucide="search" aria-hidden="true"></i>
                        {{ __('Search') }}
                    </button>

                    @if ($search !== '' || $verifiedOnly)
                        <a href="{{ route('agencies.index') }}"
                            class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 transition hover:border-indigo-500 hover:text-indigo-600">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div id="agency-results" class="transition-opacity duration-200">
                @include('website.marketplace.agencies._results')
            </div>
        </div>
    </main>
@endsection
