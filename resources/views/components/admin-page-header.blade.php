@props(['title', 'breadcrumbs' => [], 'description' => null])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif

        {{-- Breadcrumb --}}
        @if (count($breadcrumbs))
            <nav class="mt-1.5 flex" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-400 dark:text-night-500">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-300">Home</a>
                    </li>
                    @foreach ($breadcrumbs as $crumb)
                        <li class="flex items-center gap-1.5">
                            @include('admin.partials.icon', ['name' => 'chevron-right', 'class' => 'w-3 h-3'])
                            @if (isset($crumb['route']))
                                <a href="{{ route($crumb['route']) }}" class="hover:text-slate-600 dark:hover:text-slate-300">{{ $crumb['label'] }}</a>
                            @else
                                <span class="font-medium text-slate-500 dark:text-slate-300">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-2.5 sm:w-auto sm:shrink-0">{{ $actions }}</div>
    @endisset
</div>
