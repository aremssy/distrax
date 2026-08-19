@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Models\Language::where('code', app()->getLocale())->value('direction') ?? 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-branding-styles />
    @stack('head')
    <script>
        const t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="font-admin h-full bg-slate-50 dark:bg-night-950 text-slate-900 dark:text-slate-100">

    {{-- Flash messages + validation errors, handed to SweetAlert2 in app.js --}}
    @php
        $flashType = session('success') ? 'success' : (session('warning') ? 'warning' : (session('error') ? 'error' : null));
        $flashMessage = session('success') ?? session('warning') ?? session('error');
        $adminAlert = [
            'flash' => $flashType ? ['type' => $flashType, 'message' => $flashMessage] : null,
            'errors' => $errors->all(),
        ];
    @endphp
    @if ($flashType || $errors->any())
        <script>
            window.adminAlert = @json($adminAlert);
        </script>
    @endif

    <div class="flex h-full overflow-hidden">
        @include('admin.partials.sidebar')

        <div class="scrollbar-slim flex h-full flex-col flex-1 min-w-0 overflow-y-auto">
            @include('admin.partials.topbar')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="shrink-0 px-6 py-4 text-xs text-slate-400 dark:text-night-500 border-t border-slate-200 dark:border-night-700/60">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
