<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-branding-styles />
    @stack('head')
    <script>
        // Prevent dark mode flash before CSS loads
        const t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="admin-panel h-full bg-slate-50 dark:bg-night-950 text-slate-900 dark:text-slate-100">

    {{-- Flash toast (auto-dismiss) --}}
    @if (session('success') || session('error') || session('warning'))
        <div id="flash-toast" data-flash
             class="fixed top-5 right-5 left-5 sm:left-auto z-[60] max-w-sm rounded-xl shadow-xl px-4 py-3 flex items-start gap-3
                    {{ session('success') ? 'bg-emerald-600 text-white' : (session('warning') ? 'bg-amber-500 text-white' : 'bg-red-600 text-white') }}">
            <span class="flex-1 text-sm font-medium">{{ session('success') ?? session('error') ?? session('warning') }}</span>
            <button onclick="this.closest('[data-flash]').remove()" class="opacity-70 hover:opacity-100 shrink-0 mt-0.5">
                @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
            </button>
        </div>
    @endif

    <div class="flex h-full">

        {{-- Sidebar --}}
        @include('admin.partials.sidebar')

        {{-- Main column --}}
        <div class="flex flex-col flex-1 min-w-0 min-h-screen">

            {{-- Topbar --}}
            @include('admin.partials.topbar')

            {{-- Page content --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            <footer class="shrink-0 px-6 py-4 text-xs text-slate-400 dark:text-night-500 border-t border-slate-200 dark:border-night-800">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </footer>

        </div>
    </div>

    {{-- Flash auto-dismiss --}}
    <script>
        setTimeout(function () {
            const toast = document.getElementById('flash-toast');
            if (toast) {
                toast.style.transition = 'opacity 0.4s';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 400);
            }
        }, 4500);
    </script>

    @stack('scripts')

</body>
</html>
