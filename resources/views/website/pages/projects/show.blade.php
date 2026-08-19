@extends('website.layouts.master')

@section('title', $project->title.' | '.config('app.name'))

@section('content')
    <main class="min-h-screen bg-slate-50 py-6" data-purpose="project-details-page">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <div class="mb-6 flex flex-col justify-between gap-3 text-sm md:flex-row md:items-center">
                <nav class="flex flex-wrap items-center gap-1.5 text-slate-500" aria-label="Breadcrumb">
                    <a class="hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a>
                    <i class="h-3.5 w-3.5" data-lucide="chevron-right" aria-hidden="true"></i>
                    <a class="hover:text-indigo-600" href="{{ route('projects.index') }}">{{ __('New Projects') }}</a>
                    <i class="h-3.5 w-3.5" data-lucide="chevron-right" aria-hidden="true"></i>
                    <span class="max-w-64 truncate font-medium text-slate-950">{{ $project->title }}</span>
                </nav>
                <a class="inline-flex items-center gap-1.5 font-medium text-slate-500 hover:text-indigo-600" href="{{ route('projects.index') }}">
                    <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i> {{ __('Back to all projects') }}
                </a>
            </div>

            <div class="relative mb-8 overflow-hidden rounded-2xl bg-slate-200" data-purpose="project-cover">
                <div class="aspect-video w-full sm:aspect-3/1">
                    @if ($project->cover_image)
                        <img alt="{{ $project->title }}" class="h-full w-full object-cover" src="{{ asset('storage/'.$project->cover_image) }}">
                    @elseif ($project->category?->image)
                        <img alt="{{ $project->category->name }}" class="h-full w-full object-cover" src="{{ asset('storage/'.$project->category->image) }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-slate-500">
                            <i class="h-14 w-14" data-lucide="building-2" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
                <div class="absolute inset-x-0 top-0 flex items-start gap-2 p-4">
                    @if ($project->status)
                        <span class="rounded-full bg-slate-950/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white backdrop-blur">
                            {{ __(ucfirst($project->status)) }}
                        </span>
                    @endif
                    @if ($project->is_featured)
                        <span class="ml-auto flex items-center gap-1 rounded-full bg-amber-400 px-3 py-1.5 text-xs font-bold uppercase text-amber-950">
                            <i class="h-3.5 w-3.5" data-lucide="star" aria-hidden="true"></i>{{ __('Featured') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
                <div class="space-y-8">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        @if ($project->category)
                            <span class="mb-3 inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                                {{ $project->category->name }}
                            </span>
                        @endif
                        <h1 class="text-2xl font-bold text-slate-950 sm:text-3xl">{{ $project->title }}</h1>
                        <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <i class="h-4 w-4 shrink-0" data-lucide="map-pin" aria-hidden="true"></i>{{ $project->zone?->name ?? __('Location available on request') }}
                            </span>
                            @if ($project->developer_name)
                                <span class="text-slate-400">&middot;</span>
                                <span class="flex items-center gap-1.5">
                                    <i class="h-4 w-4 shrink-0" data-lucide="building-2" aria-hidden="true"></i>{{ $project->developer_name }}
                                </span>
                            @endif
                        </p>

                        <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3">
                            @if ($project->price_from)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Starting from') }}</dt>
                                    <dd class="mt-1 text-xl font-bold text-indigo-600">{{ money($project->price_from) }}</dd>
                                </div>
                            @endif
                            @if ($project->completion_date)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Completion') }}</dt>
                                    <dd class="mt-1 text-xl font-bold text-slate-950">{{ $project->completion_date->format('M Y') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                                <dd class="mt-1 text-xl font-bold text-slate-950">{{ __(ucfirst($project->status)) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950">{{ __('About this project') }}</h2>
                        @if ($project->description)
                            {{-- Sanitized on save via the SanitizedHtml cast, so it is safe to render as HTML. --}}
                            <div class="mt-4 break-words text-sm leading-7 text-slate-600
                                [&>p]:mb-4 [&>h2]:mb-2 [&>h2]:mt-6 [&>h2]:text-lg [&>h2]:font-bold [&>h2]:text-slate-950
                                [&>h3]:mb-2 [&>h3]:mt-5 [&>h3]:font-bold [&>h3]:text-slate-950
                                [&_a]:font-medium [&_a]:text-indigo-600 [&_a]:underline [&_strong]:font-semibold [&_strong]:text-slate-900
                                [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mb-1.5
                                [&_blockquote]:border-s-4 [&_blockquote]:border-indigo-500 [&_blockquote]:ps-4 [&_blockquote]:italic
                                [&_img]:h-auto [&_img]:max-w-full [&_pre]:overflow-x-auto [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto
                                lg:[&_table]:table lg:[&_table]:max-w-none lg:[&_table]:overflow-visible">
                                {!! $project->description !!}
                            </div>
                        @else
                            <p class="mt-4 text-sm leading-7 text-slate-600">{{ __('The developer has not added a description yet.') }}</p>
                        @endif
                    </section>
                </div>

                <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-slate-900">{{ __('Interested in this project?') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('Get in touch with our team for pricing, floor plans, and availability.') }}</p>
                        <a class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            href="{{ route('contact') }}">
                            <i class="h-4 w-4" data-lucide="message-square" aria-hidden="true"></i>{{ __('Enquire now') }}
                        </a>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-slate-900">{{ __('Project facts') }}</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            @if ($project->developer_name)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Developer') }}</dt><dd class="text-right font-medium text-slate-900">{{ $project->developer_name }}</dd></div>
                            @endif
                            @if ($project->zone)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Location') }}</dt><dd class="text-right font-medium text-slate-900">{{ $project->zone->name }}</dd></div>
                            @endif
                            @if ($project->category)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Category') }}</dt><dd class="text-right font-medium text-slate-900">{{ $project->category->name }}</dd></div>
                            @endif
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Status') }}</dt><dd class="text-right font-medium text-slate-900">{{ __(ucfirst($project->status)) }}</dd></div>
                            @if ($project->completion_date)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Completion') }}</dt><dd class="text-right font-medium text-slate-900">{{ $project->completion_date->format('M Y') }}</dd></div>
                            @endif
                        </dl>
                    </section>
                </aside>
            </div>
        </div>
    </main>
@endsection
