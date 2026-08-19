@php
    $menu = \App\Services\AdminMenu::items();

    // Highlight the menu item whose route group matches the current route, so a
    // detail/edit page (e.g. admin.blogs.show) keeps its parent item (Blog) active.
    // When several groups match, the most specific (longest prefix) wins so that
    // e.g. "Listing Reports" beats "All Listings" on admin.listings.reports.*.
    $activeRoute = null;
    $activePrefixLength = -1;

    foreach ($menu as $navItem) {
        if (isset($navItem['divider']) || ! isset($navItem['route'])) {
            continue;
        }

        $prefix = \Illuminate\Support\Str::beforeLast($navItem['route'], '.');

        if (request()->routeIs($prefix.'.*') && strlen($prefix) > $activePrefixLength) {
            $activePrefixLength = strlen($prefix);
            $activeRoute = $navItem['route'];
        }
    }
@endphp

<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-[transform,margin] duration-300 -translate-x-full lg:translate-x-0 lg:static lg:inset-auto dark:border-night-700/60 dark:bg-night-900">

    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center border-b border-slate-100 px-5 dark:border-night-700/60">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center">
            <x-application-logo class="h-8 w-auto dark:invert dark:hue-rotate-180" />
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="scrollbar-slim flex-1 overflow-y-auto px-3 py-4">
        @foreach ($menu as $item)
            @if (isset($item['divider']))
                <p class="px-3 pt-5 pb-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-night-500">
                    {{ $item['label'] }}
                </p>
            @elseif ($item['permission'] === 'dashboard.view' || auth()->user()->hasPermission($item['permission']))
                @php
                    $isActive = $item['route'] === $activeRoute;
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="group mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                          {{ $isActive
                              ? 'bg-brand text-white shadow-md shadow-brand/30'
                              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-night-800 dark:hover:text-white' }}">
                    @include('admin.partials.icon', ['name' => $item['icon'], 'class' => 'w-[18px] h-[18px] shrink-0 '.($isActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-600 dark:text-night-500 dark:group-hover:text-slate-300')])
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- User info footer --}}
    <div class="shrink-0 border-t border-slate-100 p-3 dark:border-night-700/60">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-night-800">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-slate-400 dark:text-night-500">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

</aside>

{{-- Mobile backdrop --}}
<div id="sidebar-backdrop"
     class="fixed inset-0 z-40 bg-black/50 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"
     onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('opacity-0','pointer-events-none');">
</div>
