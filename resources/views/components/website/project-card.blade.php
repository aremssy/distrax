@props(['project'])

@php
    $coverUrl = match (true) {
        (bool) $project->cover_image => asset('storage/'.$project->cover_image),
        (bool) $project->category?->image => asset('storage/'.$project->category->image),
        default => null,
    };
    $statusStyles = match ($project->status) {
        'ongoing' => 'bg-amber-400 text-amber-950',
        'completed' => 'bg-emerald-500 text-white',
        default => 'bg-indigo-600 text-white',
    };
@endphp

<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
    <div class="relative aspect-video overflow-hidden bg-slate-100">
        @if ($coverUrl)
            <img alt="{{ $project->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                src="{{ $coverUrl }}" loading="lazy" decoding="async" width="640" height="360">
        @else
            <div class="flex h-full w-full items-center justify-center text-slate-300">
                <i class="h-10 w-10" data-lucide="building-2" aria-hidden="true"></i>
            </div>
        @endif

        <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
            @if ($project->status)
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide backdrop-blur {{ $statusStyles }}">
                    {{ ucfirst($project->status) }}
                </span>
            @endif
            @if ($project->is_featured)
                <span class="ml-auto flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold uppercase text-amber-600 backdrop-blur">
                    <i class="h-3 w-3 fill-current" data-lucide="star" aria-hidden="true"></i>Featured
                </span>
            @endif
        </div>

        @if ($project->category)
            <span class="absolute bottom-3 start-3 inline-flex rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 backdrop-blur">
                {{ $project->category->name }}
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-bold text-slate-950">
            <a class="line-clamp-1 transition-colors after:absolute after:inset-0 group-hover:text-indigo-600"
                href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>
        </h3>
        <p class="mt-1.5 flex items-center gap-1 text-sm text-slate-600">
            <i class="h-3.5 w-3.5 shrink-0 text-slate-400" data-lucide="map-pin" aria-hidden="true"></i>
            <span class="line-clamp-1">{{ $project->zone?->name ?? 'Location available on request' }}</span>
        </p>
        @if ($project->developer_name)
            <p class="mt-1 flex items-center gap-1 text-sm text-slate-500">
                <i class="h-3.5 w-3.5 shrink-0 text-slate-400" data-lucide="hard-hat" aria-hidden="true"></i>
                <span class="line-clamp-1">by {{ $project->developer_name }}</span>
            </p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
            <div class="min-w-0">
                @if ($project->price_from)
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Starting from</p>
                    <p class="truncate text-lg font-bold text-indigo-600">{{ money($project->price_from) }}</p>
                @else
                    <p class="text-sm font-semibold text-slate-600">Price on request</p>
                @endif
            </div>
            @if ($project->completion_date)
                <p class="flex shrink-0 items-center gap-1 text-xs font-medium text-slate-600">
                    <i class="h-3.5 w-3.5" data-lucide="calendar-check" aria-hidden="true"></i>
                    {{ $project->completion_date->format('M Y') }}
                </p>
            @endif
        </div>
    </div>
</article>
