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
    <script>
        // Prevent dark mode flash
        const t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="h-full bg-slate-950 dark:bg-night-950">

    <div class="flex min-h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">

        {{-- Logo / Brand --}}
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="mb-3 inline-flex rounded-xl bg-white px-5 py-3 shadow-xl shadow-black/20">
                <x-application-logo class="h-9 w-auto" />
            </div>
            <p class="text-sm text-slate-400">Administration Panel</p>
        </div>

        {{-- Card --}}
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-night-900 py-8 px-6 shadow-2xl rounded-2xl border border-slate-200 dark:border-night-800">

                {{-- Flash messages --}}
                @if (session('success'))
                    <div class="mb-5 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-5 rounded-lg bg-red-50 dark:bg-red-500/15 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>

    </div>

</body>
</html>
