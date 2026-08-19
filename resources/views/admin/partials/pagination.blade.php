@php
    /* Render via `$paginator->links('admin.partials.pagination')` (Laravel injects $elements),
       or via @include with just a $paginator — this fallback builds $elements then. */
    if (! isset($elements)) {
        $window = \Illuminate\Pagination\UrlWindow::make($paginator);
        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
@endphp

@if ($paginator->total() > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-night-800">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing {{ number_format($paginator->firstItem()) }} to {{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }} entries
        </p>

        @if ($paginator->hasPages())
            <nav role="navigation" aria-label="Pagination" class="flex max-w-full flex-nowrap items-center gap-1.5 overflow-x-auto whitespace-nowrap [&>*]:shrink-0">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-300 dark:border-night-700 dark:text-night-600">
                        @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'w-3.5 h-3.5'])
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                       class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-night-700 dark:text-slate-400 dark:hover:bg-night-800">
                        @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'w-3.5 h-3.5'])
                    </a>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="flex h-8 w-8 items-end justify-center pb-1 text-xs font-medium text-slate-400 dark:text-night-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-xs font-semibold text-white shadow-md shadow-brand/30">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-night-700 dark:text-slate-300 dark:hover:bg-night-800">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                       class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-night-700 dark:text-slate-400 dark:hover:bg-night-800">
                        @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                    </a>
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-300 dark:border-night-700 dark:text-night-600">
                        @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif
