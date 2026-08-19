@if (($projects ?? collect())->isNotEmpty())
    <section class="bg-slate-50 py-14 sm:py-20" data-purpose="new-projects" data-reveal>
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <div class="mb-8 flex flex-col justify-between gap-4 sm:mb-10 md:flex-row md:items-end" data-reveal-item>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">{{ __('New development') }}</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('New Projects') }}</h2>
                    <div class="mt-3 h-1 w-16 rounded bg-indigo-600"></div>
                </div>
                <a class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    href="{{ route('projects.index') }}">
                    {{ __('View all projects') }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-children>
                @foreach ($projects as $project)
                    <x-website.project-card :$project />
                @endforeach
            </div>
        </div>
    </section>
@endif
