@extends('website.layouts.master')

@section('title', __('New Development Projects').' | '.config('app.name'))

@section('content')
    <main class="min-h-screen bg-slate-50 py-14 sm:py-20" data-purpose="projects-index-page">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <div class="mb-10 flex flex-col gap-4 sm:mb-14 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">{{ __('New development') }}</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950 sm:text-4xl">{{ __('New Development Projects') }}</h1>
                    <p class="mt-3 max-w-xl text-sm text-slate-500 sm:text-base">{{ __('Explore upcoming and ongoing developments from trusted builders, from groundbreaking to move-in ready.') }}</p>
                </div>
                @if ($projects->total())
                    <p class="text-sm font-medium text-slate-500">{{ __(':count :label listed', ['count' => number_format($projects->total()), 'label' => \Illuminate\Support\Str::plural('project', $projects->total())]) }}</p>
                @endif
            </div>

            @if ($projects->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('No projects yet') }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ __('Check back soon — new developments are added regularly.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-website.project-card :$project />
                    @endforeach
                </div>

                <div class="mt-10">{{ $projects->links() }}</div>
            @endif
        </div>
    </main>
@endsection
