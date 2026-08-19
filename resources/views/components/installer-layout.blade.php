<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Installer' }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    @vite(['resources/css/app.css'])
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <x-application-logo class="mx-auto mb-4 h-12 w-auto" />
                <h1 class="text-2xl font-bold">{{ config('app.name') }} Installer</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Set up your application in just a few steps</p>
            </div>

            {{-- Steps bar --}}
            @php
                $steps = [
                    ['route' => 'installer.requirements', 'label' => 'Requirements'],
                    ['route' => 'installer.database', 'label' => 'Database'],
                    ['route' => 'installer.admin', 'label' => 'Admin'],
                    ['route' => 'installer.settings', 'label' => 'Settings'],
                    ['route' => 'installer.license', 'label' => 'License'],
                    ['route' => 'installer.finish', 'label' => 'Finish'],
                ];
                $current = Route::currentRouteName();
                $currentIndex = collect($steps)->search(fn ($s) => $s['route'] === $current);
            @endphp
            <nav class="mb-8 -mx-4 px-4 overflow-x-auto sm:mx-0 sm:px-0">
                <ol class="flex flex-nowrap items-center justify-start gap-1 text-xs font-medium whitespace-nowrap sm:justify-center">
                    @foreach ($steps as $i => $step)
                        <li class="flex items-center gap-1">
                            @if ($i > 0)
                                <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            @endif
                            <span class="px-2 py-1 rounded-full {{ $i === $currentIndex ? 'bg-indigo-600 text-white' : ($i < $currentIndex ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-400') }}">
                                {{ $step['label'] }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            {{-- Content --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 sm:p-8">
                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-600">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
