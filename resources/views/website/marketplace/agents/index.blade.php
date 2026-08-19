@extends('website.layouts.master')

@section('title', __('Real Estate Agents').' | '.config('app.name'))

@section('content')
    <main class="min-h-screen bg-slate-50 py-10 sm:py-14" data-purpose="agents-index-page">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <nav aria-label="Breadcrumb" class="mb-6">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <li><a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li class="font-semibold text-slate-950" aria-current="page">{{ __('Agents') }}</li>
                </ol>
            </nav>

            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">{{ __('Our Agents') }}</h1>
                <p class="mt-3 max-w-md text-base text-slate-600">
                    {{ __('Find professional and trusted agents to help you buy, sell or rent the perfect property.') }}
                </p>
            </div>

            <form action="{{ route('agents.index') }}" method="GET" role="search" data-purpose="agents-search" data-live-search="#agent-results"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex min-w-0 flex-1 items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                        <i class="h-5 w-5 shrink-0 text-slate-500" data-lucide="search" aria-hidden="true"></i>
                        <label class="sr-only" for="agent-search">{{ __('Search agents') }}</label>
                        <input id="agent-search" type="search" name="q" value="{{ $search }}"
                            placeholder="{{ __('Search by name, title or agency...') }}"
                            class="h-12 min-w-0 flex-1 border-0 bg-transparent text-base text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                    </div>
                    <button type="submit"
                        class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-7 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        <i class="h-4 w-4" data-lucide="search" aria-hidden="true"></i>
                        {{ __('Search') }}
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('agents.index') }}"
                            class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 transition hover:border-indigo-500 hover:text-indigo-600">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <div id="agent-results" class="transition-opacity duration-200">
                @include('website.marketplace.agents._results')
            </div>
        </div>
    </main>
@endsection
