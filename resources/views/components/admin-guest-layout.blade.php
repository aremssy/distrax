@props([
    'illustrated' => false,
    'title' => 'Admin',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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
    <script>
        const t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="font-admin h-full {{ $illustrated ? 'bg-[#f7f8ff] text-slate-900 dark:bg-night-950' : 'bg-night-950' }}">
    @if ($illustrated)
        <main class="relative isolate flex min-h-full items-center justify-center overflow-hidden px-4 py-6 sm:px-6 sm:py-10 lg:px-10">
            <img src="{{ asset('assets/images/admin/auth/admin-login-background.webp') }}" alt="" class="absolute inset-0 -z-20 size-full object-cover dark:hidden">
            <div aria-hidden="true" class="absolute -left-52 top-1/3 -z-10 h-[34rem] w-[34rem] rounded-full bg-indigo-200/35 blur-3xl dark:bg-brand/10"></div>
            <div aria-hidden="true" class="absolute -right-40 -top-56 -z-10 h-[32rem] w-[32rem] rounded-full bg-sky-100/70 blur-3xl dark:bg-indigo-900/20"></div>
            <div aria-hidden="true" class="absolute left-5 top-6 -z-10 h-28 w-28 bg-[radial-gradient(circle,#c7d2fe_2px,transparent_2.5px)] bg-[size:24px_24px] opacity-60"></div>
            <div aria-hidden="true" class="absolute bottom-7 right-5 -z-10 h-28 w-28 bg-[radial-gradient(circle,#c7d2fe_2px,transparent_2.5px)] bg-[size:24px_24px] opacity-60"></div>

            <div class="grid w-full max-w-6xl overflow-hidden rounded-[1.75rem] border border-white/70 bg-white/20 shadow-[0_32px_80px_-32px_rgba(67,56,202,0.28)] backdrop-blur-sm dark:border-night-700/70 dark:bg-night-900/30 dark:backdrop-blur-xl lg:grid-cols-[0.88fr_1.12fr]">
                <aside class="relative hidden min-h-[46rem] overflow-hidden border-r border-white/60 bg-indigo-50/20 px-12 py-11 lg:flex lg:flex-col dark:border-night-700/70 dark:bg-night-900/15">
                    <x-application-logo class="h-12 w-auto self-start" />

                    <div class="mt-9">
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                            Welcome to <span class="text-brand">Rentdo</span> Admin
                        </h2>
                        <p class="mt-3 max-w-sm text-base leading-7 text-slate-600 dark:text-slate-300">
                            Manage your properties, users and business from one powerful dashboard.
                        </p>
                    </div>

                    <img
                        src="{{ asset('assets/images/admin/auth/property-admin-login.webp') }}"
                        alt="A modern rental home"
                        class="mt-4 min-h-0 w-full flex-1 object-contain object-center drop-shadow-[0_24px_24px_rgba(79,70,229,0.16)]"
                    >

                    <div class="mt-7 grid grid-cols-3 gap-4 text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand text-white">
                                @include('admin.partials.icon', ['name' => 'shield-check', 'class' => 'size-4.5'])
                            </span>
                            <span><strong class="block text-xs text-slate-900 dark:text-white">Secure</strong><small class="block text-[10px]">Data protection</small></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand text-white">
                                @include('admin.partials.icon', ['name' => 'chart-bar', 'class' => 'size-4.5'])
                            </span>
                            <span><strong class="block text-xs text-slate-900 dark:text-white">Analytics</strong><small class="block text-[10px]">Business insights</small></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand text-white">
                                @include('admin.partials.icon', ['name' => 'arrow-trending-up', 'class' => 'size-4.5'])
                            </span>
                            <span><strong class="block text-xs text-slate-900 dark:text-white">Fast</strong><small class="block text-[10px]">Performance</small></span>
                        </div>
                    </div>
                </aside>

                <section class="flex min-h-[46rem] items-center px-6 py-10 sm:px-12 lg:px-16">
                    <div class="mx-auto w-full max-w-lg">
                        <div class="mb-7 lg:hidden">
                            <x-application-logo class="h-10 w-auto" />
                        </div>

                        @if (session('success'))
                            <div role="status" class="mb-7 flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3.5 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-500 text-white">
                                    @include('admin.partials.icon', ['name' => 'check', 'class' => 'size-3.5'])
                                </span>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div role="alert" class="mb-7 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                                {{ session('error') }}
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>
    @else
        <div class="flex min-h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="text-center sm:mx-auto sm:w-full sm:max-w-md">
                <div class="mb-3 inline-flex rounded-2xl bg-white px-5 py-3 shadow-xl shadow-black/25">
                    <x-application-logo class="h-9 w-auto" />
                </div>
                <p class="text-sm text-night-500">Administration Panel</p>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-2xl dark:border-night-700 dark:bg-night-900">
                    @if (session('success'))
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </div>
    @endif

</body>
</html>
