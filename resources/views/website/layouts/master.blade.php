<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage?->direction ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ setting('site_favicon') ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('site_favicon')) : asset('assets/favicon.svg') }}">

    {{-- Inter and Noto Sans Bengali are self-hosted: @fonts inlines the @font-face CSS, so there's no render-blocking request to Google Fonts. Configured via bunny() in vite.config.js. --}}
    @fonts
    {{-- Lucide icons are bundled and loaded on demand from resources/js/app.js (no third-party render-blocking script). --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-branding-styles />

    {{-- Sensible social-share defaults; individual pages override via @push('meta'). --}}
    <meta name="description" content="@yield('meta_description', setting('site_tagline') ?? config('app.name'))">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', setting('site_tagline') ?? config('app.name'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', (setting('og_image') ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('og_image')) : asset('assets/og-image.png')))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('meta_description', setting('site_tagline') ?? config('app.name'))">
    <meta name="twitter:image" content="@yield('og_image', (setting('og_image') ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('og_image')) : asset('assets/og-image.png')))">
    @stack('meta')
    @stack('head')
</head>

<body class="min-w-0 overflow-x-hidden bg-white text-slate-800"
    style="font-family: {{ ($currentLanguage?->code ?? null) === 'bn' ? "'Noto Sans Bengali', sans-serif" : "'Inter', sans-serif" }};">
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:inset-s-4 focus:top-4 focus:z-100 focus:rounded-lg focus:bg-indigo-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        {{ __('Skip to main content') }}
    </a>
    @if (! request()->routeIs('login', 'register'))
    <div class="relative z-60 border-b border-white/10 bg-slate-950 text-[13px] font-medium text-slate-200" data-purpose="top-navigation-bar">
        <div class="mx-auto flex h-12 max-w-7xl items-center justify-end gap-4 px-4 sm:justify-between md:px-6">
            <div class="hidden min-w-0 items-center gap-4 sm:flex">
                @if (setting('site_phone'))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('site_phone')) }}"
                        class="flex items-center gap-2 transition hover:text-white">
                        <i class="h-4 w-4 text-indigo-400" data-lucide="phone" aria-hidden="true"></i>
                        {{ setting('site_phone') }}
                    </a>
                @endif
                @if (setting('site_email'))
                    <span class="hidden h-4 w-px bg-white/15 md:block" aria-hidden="true"></span>
                    <a href="mailto:{{ setting('site_email') }}"
                        class="hidden items-center gap-2 transition hover:text-white md:flex">
                        <i class="h-4 w-4 text-indigo-400" data-lucide="mail" aria-hidden="true"></i>
                        {{ setting('site_email') }}
                    </a>
                @endif
                <span class="hidden h-4 w-px bg-white/15 lg:block" aria-hidden="true"></span>
                <div x-data="{ open: false, query: '' }" @click.outside="open = false" @keydown.escape.window="open = false"
                    class="relative hidden lg:block">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox"
                        aria-label="Choose your location" class="flex items-center gap-2 py-1 transition hover:text-white">
                        <i class="h-4 w-4 text-indigo-400" data-lucide="map-pin" aria-hidden="true"></i>
                        {{ $currentZone?->name ?? 'Choose location' }}
                        <span class="transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <i class="h-3.5 w-3.5 text-slate-400" data-lucide="chevron-down" aria-hidden="true"></i>
                        </span>
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                        class="absolute start-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-slate-100 bg-white font-normal text-slate-600 shadow-xl">
                        <div class="border-b border-slate-100 p-2">
                            <input type="search" x-model="query" placeholder="Search location…" aria-label="Search location"
                                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-[13px] text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                        <form action="{{ route('zone.select') }}" method="POST" class="max-h-64 overflow-y-auto p-1.5">
                            @csrf
                            @forelse ($zoneOptions ?? [] as $zone)
                                <button type="submit" name="zone_id" value="{{ $zone->id }}"
                                    x-show="query === '' || {{ Js::from(Str::lower($zone->name)) }}.includes(query.toLowerCase())"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start transition {{ ($currentZone?->id ?? null) === $zone->id ? 'bg-indigo-50 font-semibold text-indigo-600' : 'hover:bg-slate-50 hover:text-indigo-600' }}">
                                    {{ $zone->name }}
                                    @if (($currentZone?->id ?? null) === $zone->id)
                                        <i class="h-3.5 w-3.5" data-lucide="check" aria-hidden="true"></i>
                                    @endif
                                </button>
                            @empty
                                <span class="block px-3 py-2 text-slate-500">No locations available</span>
                            @endforelse
                        </form>
                    </div>
                </div>
            </div>
            <div class="flex min-w-0 items-center gap-2 whitespace-nowrap sm:gap-4">
                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox"
                        aria-label="Choose your language" class="flex max-w-28 items-center gap-1.5 py-1 transition hover:text-white sm:max-w-none">
                        <i class="hidden h-4 w-4 text-indigo-400 sm:block" data-lucide="globe" aria-hidden="true"></i>
                        <span class="truncate">{{ $currentLanguage?->native_name ?: ($currentLanguage?->name ?? 'English') }}</span>
                        <span class="transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <i class="h-3.5 w-3.5 text-slate-400" data-lucide="chevron-down" aria-hidden="true"></i>
                        </span>
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                        class="absolute end-0 top-full z-50 mt-2 w-44 rounded-xl border border-slate-100 bg-white font-normal text-slate-600 shadow-xl">
                        <form action="{{ route('locale.select') }}" method="POST" class="max-h-64 overflow-y-auto p-1.5">
                            @csrf
                            @forelse ($languageOptions ?? [] as $language)
                                <button type="submit" name="locale" value="{{ $language->code }}"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start transition {{ ($currentLanguage?->code ?? null) === $language->code ? 'bg-indigo-50 font-semibold text-indigo-600' : 'hover:bg-slate-50 hover:text-indigo-600' }}">
                                    {{ $language->native_name ?: $language->name }}
                                    @if (($currentLanguage?->code ?? null) === $language->code)
                                        <i class="h-3.5 w-3.5" data-lucide="check" aria-hidden="true"></i>
                                    @endif
                                </button>
                            @empty
                                <span class="block px-3 py-2 text-slate-500">English</span>
                            @endforelse
                        </form>
                    </div>
                </div>
                <span class="h-4 w-px bg-white/15" aria-hidden="true"></span>
                <div x-data="{ open: false, query: '' }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox"
                        aria-label="Choose your currency"
                        class="flex items-center gap-1.5 rounded-full py-1 pl-1.5 pr-2 -mx-1.5 -my-0 transition hover:bg-white/10 hover:text-white"
                        :class="open ? 'bg-white/10 text-white' : ''">
                        <span class="truncate font-semibold">{{ $currentCurrency?->symbol ?? '$' }} {{ $currentCurrency?->code ?? 'USD' }}</span>
                        <span class="transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <i class="h-3.5 w-3.5 text-slate-400" data-lucide="chevron-down" aria-hidden="true"></i>
                        </span>
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                        class="absolute end-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-100 bg-white font-normal text-slate-600 shadow-xl">
                        <p class="border-b border-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ __('Currency') }}
                        </p>
                        @if (count($currencyOptions ?? []) > 6)
                            <div class="border-b border-slate-100 p-2">
                                <input type="search" x-model="query" placeholder="{{ __('Search currency…') }}" aria-label="{{ __('Search currency') }}"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-[13px] text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                            </div>
                        @endif
                        <form action="{{ route('currency.select') }}" method="POST" class="max-h-64 overflow-y-auto p-1.5">
                            @csrf
                            @forelse ($currencyOptions ?? [] as $currency)
                                <button type="submit" name="currency" value="{{ $currency->code }}"
                                    x-show="query === '' || {{ Js::from(Str::lower($currency->code.' '.$currency->name)) }}.includes(query.toLowerCase())"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start transition {{ ($currentCurrency?->code ?? null) === $currency->code ? 'bg-indigo-50 font-semibold text-indigo-600' : 'hover:bg-slate-50 hover:text-indigo-600' }}">
                                    <span class="flex items-center gap-2 truncate">
                                        <span class="w-5 shrink-0 text-center text-slate-400">{{ $currency->symbol }}</span>
                                        <span class="truncate">{{ $currency->code }} · {{ $currency->name }}</span>
                                    </span>
                                    @if (($currentCurrency?->code ?? null) === $currency->code)
                                        <i class="h-3.5 w-3.5 shrink-0" data-lucide="check" aria-hidden="true"></i>
                                    @endif
                                </button>
                            @empty
                                <span class="block px-3 py-2 text-slate-500">USD</span>
                            @endforelse
                        </form>
                    </div>
                </div>
                @auth
                    <a class="flex items-center gap-1.5 transition hover:text-white" href="{{ route('dashboard') }}">
                        <i class="h-4 w-4 text-indigo-400" data-lucide="user" aria-hidden="true"></i> Dashboard
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="transition hover:text-white" type="submit">Logout</button>
                    </form>
                @else
                    <a class="flex items-center gap-1.5 transition hover:text-white" href="{{ route('login') }}">
                        <i class="h-4 w-4 text-indigo-400" data-lucide="user" aria-hidden="true"></i> Login
                    </a>
                    <a class="hidden rounded-full bg-indigo-500 px-3.5 py-1.5 font-semibold text-white transition hover:bg-indigo-400 sm:inline-flex"
                        href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </div>

    <header class="sticky top-0 z-50 bg-white shadow-sm" data-purpose="main-header">
        <nav class="max-w-7xl mx-auto px-4 md:px-6 py-3 md:py-4 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" aria-label="Rentdo home" class="shrink-0">
                <x-application-logo class="h-8 w-auto md:h-10" />
            </a>
            <ul class="hidden xl:flex items-center gap-7 2xl:gap-8 text-[15px] font-medium text-slate-600">
                <li><a class="text-indigo-500" href="{{ url('/') }}">Home</a></li>

                <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        class="flex items-center gap-1 py-2 transition [&_svg]:transition-transform" :class="{ 'text-indigo-500 [&_svg]:rotate-180': open }">
                        Listings <i class="h-4 w-4" data-lucide="chevron-down"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.left
                        class="absolute left-0 top-full z-20 mt-1 w-56 rounded-xl border border-slate-100 bg-white p-2 shadow-xl">
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('properties.index', ['type' => 'sale']) }}">For Sale</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('properties.index', ['type' => 'rent']) }}">For Rent</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('properties.index', ['type' => 'land']) }}">Commercial &amp; Land</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('properties.index', ['featured' => 1]) }}">Featured Listings</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('properties.index', ['verified' => 1]) }}">Verified Listings</a>
                    </div>
                </li>

                <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        class="flex items-center gap-1 py-2 transition [&_svg]:transition-transform" :class="{ 'text-indigo-500 [&_svg]:rotate-180': open }">
                        Property <i class="h-4 w-4" data-lucide="chevron-down"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.left
                        class="absolute left-0 top-full z-20 mt-1 w-56 rounded-xl border border-slate-100 bg-white p-2 shadow-xl">
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('owner.listings.create') }}">Add Property</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('owner.listings.index') }}">My Listings</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('dashboard.compare') }}">Compare Properties</a>
                    </div>
                </li>

                <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        class="flex items-center gap-1 py-2 transition [&_svg]:transition-transform" :class="{ 'text-indigo-500 [&_svg]:rotate-180': open }">
                        Agents <i class="h-4 w-4" data-lucide="chevron-down"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.left
                        class="absolute left-0 top-full z-20 mt-1 w-56 rounded-xl border border-slate-100 bg-white p-2 shadow-xl">
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('agents.index') }}">All Agents</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('agencies.index') }}">Agencies</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('technicians.index') }}">Technicians</a>
                        <a class="block rounded-lg px-3 py-2 font-semibold text-indigo-600 hover:bg-slate-50" href="{{ route('technician.apply') }}">Become a Technician</a>
                    </div>
                </li>

                <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                        class="flex items-center gap-1 py-2 transition [&_svg]:transition-transform" :class="{ 'text-indigo-500 [&_svg]:rotate-180': open }">
                        System <i class="h-4 w-4" data-lucide="chevron-down"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.left
                        class="absolute left-0 top-full z-20 mt-1 w-56 rounded-xl border border-slate-100 bg-white p-2 shadow-xl">
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('pages.show', 'about-us') }}">About Us</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('blog.index') }}">Blog</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('faq') }}">FAQ</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('pages.show', 'terms-of-service') }}">Terms &amp; Conditions</a>
                        <a class="block rounded-lg px-3 py-2 hover:bg-slate-50 hover:text-indigo-600" href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a>
                    </div>
                </li>

                <li><a class="hover:text-indigo-500" href="{{ route('contact') }}">Contact</a></li>
            </ul>
            <div class="hidden xl:flex items-center gap-3 shrink-0">
                <a href="{{ route('owner.listings.create') }}"
                    class="bg-indigo-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-600 transition">Add
                    Property</a>
            </div>
            <button type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-nav-menu"
                aria-label="Toggle navigation menu"
                class="xl:hidden inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                <i class="w-5 h-5" data-mobile-menu-icon="open" data-lucide="menu"></i>
                <i class="w-5 h-5 hidden" data-mobile-menu-icon="close" data-lucide="x"></i>
            </button>
        </nav>

        <div id="mobile-nav-menu" data-mobile-menu class="hidden max-h-[calc(100dvh-6rem)] overflow-y-auto border-t border-slate-100 bg-white xl:hidden">
            <ul class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-1 text-sm font-medium text-slate-600">
                <li><a class="block py-2 text-indigo-500" href="{{ url('/') }}">Home</a></li>

                <li>
                    <button type="button" data-mobile-submenu-toggle aria-expanded="false"
                        class="flex w-full items-center justify-between py-2 hover:text-indigo-500">
                        Listings <i class="h-4 w-4 transition" data-mobile-submenu-icon data-lucide="chevron-down"></i>
                    </button>
                    <ul data-mobile-submenu class="hidden flex-col gap-1 py-1 pl-4 text-slate-500">
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('properties.index', ['type' => 'sale']) }}">For Sale</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('properties.index', ['type' => 'rent']) }}">For Rent</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('properties.index', ['type' => 'land']) }}">Commercial &amp; Land</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('properties.index', ['featured' => 1]) }}">Featured Listings</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('properties.index', ['verified' => 1]) }}">Verified Listings</a></li>
                    </ul>
                </li>

                <li>
                    <button type="button" data-mobile-submenu-toggle aria-expanded="false"
                        class="flex w-full items-center justify-between py-2 hover:text-indigo-500">
                        Property <i class="h-4 w-4 transition" data-mobile-submenu-icon data-lucide="chevron-down"></i>
                    </button>
                    <ul data-mobile-submenu class="hidden flex-col gap-1 py-1 pl-4 text-slate-500">
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('owner.listings.create') }}">Add Property</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('owner.listings.index') }}">My Listings</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('dashboard.compare') }}">Compare Properties</a></li>
                    </ul>
                </li>

                <li>
                    <button type="button" data-mobile-submenu-toggle aria-expanded="false"
                        class="flex w-full items-center justify-between py-2 hover:text-indigo-500">
                        Agents <i class="h-4 w-4 transition" data-mobile-submenu-icon data-lucide="chevron-down"></i>
                    </button>
                    <ul data-mobile-submenu class="hidden flex-col gap-1 py-1 pl-4 text-slate-500">
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('agents.index') }}">All Agents</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('agencies.index') }}">Agencies</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('technicians.index') }}">Technicians</a></li>
                        <li><a class="block py-1.5 font-semibold text-indigo-600" href="{{ route('technician.apply') }}">Become a Technician</a></li>
                    </ul>
                </li>

                <li>
                    <button type="button" data-mobile-submenu-toggle aria-expanded="false"
                        class="flex w-full items-center justify-between py-2 hover:text-indigo-500">
                        System <i class="h-4 w-4 transition" data-mobile-submenu-icon data-lucide="chevron-down"></i>
                    </button>
                    <ul data-mobile-submenu class="hidden flex-col gap-1 py-1 pl-4 text-slate-500">
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('pages.show', 'about-us') }}">About Us</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('blog.index') }}">Blog</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('faq') }}">FAQ</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('pages.show', 'terms-of-service') }}">Terms &amp; Conditions</a></li>
                        <li><a class="block py-1.5 hover:text-indigo-500" href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a></li>
                    </ul>
                </li>

                <li><a class="block py-2 hover:text-indigo-500" href="{{ route('contact') }}">Contact</a></li>
            </ul>
            <div class="max-w-7xl mx-auto px-4 pb-5 flex flex-col gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('owner.listings.create') }}"
                    class="w-full text-center bg-indigo-500 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-600 transition">Add
                    Property</a>
            </div>
        </div>
    </header>

    @endif

    <div id="main-content">
        @yield('content')
    </div>

    @if (! request()->routeIs('login', 'register'))
    <footer class=" text-slate-600 relative">
        <div class="mx-auto max-w-7xl px-4 pb-10 pt-12 sm:px-6 sm:pt-16 lg:pt-20">
            <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-[1.15fr_0.9fr_0.9fr_0.9fr_1.1fr] lg:gap-12">
                <div class="sm:col-span-2 lg:col-span-1">
                    <x-application-logo class="h-12 w-auto" />
                    <p class="mt-10 max-w-sm text-[17px] leading-9 text-slate-500">
                        {{ __('The most trusted platform to buy, sell, rent and discover verified properties with confidence.') }}
                    </p>
                    @php
                        $footerSocials = [
                            'Facebook' => ['url' => setting('social_facebook'), 'path' => 'M13.5 9H16V6h-2.5c-1.9 0-3.5 1.6-3.5 3.5V12H8v3h2v6h3v-6h2.5l.5-3H13v-2.5c0-.3.2-.5.5-.5Z'],
                            'Twitter' => ['url' => setting('social_twitter'), 'path' => 'M4 5.5 10.1 13 4 18.5h2.8l4.5-4.1 3.3 4.1H20l-6.4-8 5.7-5H16l-4 3.5-2.8-3.5H4Z'],
                            'Instagram' => ['url' => setting('social_instagram'), 'path' => 'M12 7.2A4.8 4.8 0 1 0 12 16.8 4.8 4.8 0 0 0 12 7.2Zm0 7.8A3 3 0 1 1 12 9a3 3 0 0 1 0 6Zm6.2-8.1a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM17 3H7a4 4 0 0 0-4 4v10a4 4 0 0 0 4 4h10a4 4 0 0 0 4-4V7a4 4 0 0 0-4-4Zm2 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10Z'],
                            'YouTube' => ['url' => setting('social_youtube'), 'path' => 'M21.6 7.3a2.9 2.9 0 0 0-2.1-2.1C17.6 4.7 12 4.7 12 4.7s-5.6 0-7.5.5a2.9 2.9 0 0 0-2.1 2.1A30.3 30.3 0 0 0 1.9 12a30.3 30.3 0 0 0 .5 4.7 2.9 2.9 0 0 0 2.1 2.1c1.9.5 7.5.5 7.5.5s5.6 0 7.5-.5a2.9 2.9 0 0 0 2.1-2.1 30.3 30.3 0 0 0 .5-4.7 30.3 30.3 0 0 0-.5-4.7ZM10 15.2V8.8L15.6 12 10 15.2Z'],
                        ];
                    @endphp
                    @if (collect($footerSocials)->pluck('url')->filter()->isNotEmpty())
                        <div class="mt-10 flex flex-nowrap gap-2">
                            @foreach ($footerSocials as $network => $meta)
                                @if ($meta['url'])
                                    <a href="{{ $meta['url'] }}" target="_blank" rel="noopener" aria-label="{{ $network }}"
                                        class="inline-flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-500 hover:text-white">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-5 w-5 fill-current">
                                            <path d="{{ $meta['path'] }}" />
                                        </svg>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">{{ __('Company') }}</h3>
                    <div class="mt-4 h-0.5 w-8 bg-indigo-500"></div>
                    <ul class="mt-7 space-y-6 text-[17px] text-slate-500">
                        <li><a href="{{ route('pages.show', 'about-us') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('About Us') }}</a></li>
                        <li><a href="{{ route('blog.index') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Blog') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Contact Us') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">{{ __('Property') }}</h3>
                    <div class="mt-4 h-0.5 w-8 bg-indigo-500"></div>
                    <ul class="mt-7 space-y-6 text-[17px] text-slate-500">
                        <li><a href="{{ route('properties.index', ['type' => 'sale']) }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Buy Property') }}</a></li>
                        <li><a href="{{ route('properties.index', ['type' => 'rent']) }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Rent Property') }}</a></li>
                        <li><a href="{{ route('properties.index', ['type' => 'land']) }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Commercial') }}</a></li>
                        <li><a href="{{ route('projects.index') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('New Projects') }}</a></li>
                        <li><a href="{{ route('properties.index') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Property by Location') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">{{ __('Support') }}</h3>
                    <div class="mt-4 h-0.5 w-8 bg-indigo-500"></div>
                    <ul class="mt-7 space-y-6 text-[17px] text-slate-500">
                        <li><a href="{{ route('faq') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('FAQ') }}</a></li>
                        <li><a href="{{ route('pages.show', 'terms-of-service') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Terms & Conditions') }}</a></li>
                        <li><a href="{{ route('pages.show', 'privacy-policy') }}" class="flex items-center gap-3 transition hover:text-indigo-600"><span class="text-indigo-500">›</span> {{ __('Privacy Policy') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">{{ __('Newsletter') }}</h3>
                    <div class="mt-4 h-0.5 w-8 bg-indigo-500"></div>
                    <p class="mt-7 max-w-sm text-[17px] leading-8 text-slate-500">
                        {{ __('Subscribe to get the latest properties, offers and real estate news.') }}
                    </p>
                    <form action="{{ route('newsletter.store') }}" method="POST" class="mt-9 max-w-md">
                        @csrf
                        <input type="hidden" name="source" value="footer">
                        <div class="flex min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <label class="sr-only" for="footer-newsletter-email">Email address</label>
                            <input id="footer-newsletter-email" type="email" name="email" required placeholder="{{ __('Enter your email') }}"
                                class="h-14 min-w-0 flex-1 border-0 px-4 text-base text-slate-600 outline-none focus:ring-0 sm:h-16 sm:px-6 sm:text-[17px]">
                            <button type="submit" aria-label="Subscribe"
                                class="inline-flex h-16 w-16 shrink-0 items-center justify-center bg-indigo-600 text-white transition hover:bg-indigo-700">
                                <svg viewBox="0 0 24 24" aria-hidden="true" class="h-6 w-6 fill-none stroke-current stroke-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 3 10 14" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 3 14.5 21 10 14 3 10.5 21 3Z" />
                                </svg>
                            </button>
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </div>

            <div class="mt-16 border-t border-slate-200/90 pt-8">
                <div class="flex flex-col gap-6 text-sm text-slate-500 lg:flex-row lg:items-center lg:justify-between">
                    <div>© {{ date('Y') }} {{ __('Rentdo. All rights reserved.') }}</div>
                    <div class="flex items-center gap-4 lg:gap-6">
                        <a href="{{ route('pages.show', 'privacy-policy') }}" class="transition hover:text-indigo-600">Privacy Policy</a>
                        <span class="hidden h-5 w-px bg-slate-300 sm:block"></span>
                        <a href="{{ route('pages.show', 'terms-of-service') }}" class="transition hover:text-indigo-600">Terms &amp; Conditions</a>
                    </div>
                    <div class="flex items-center gap-2">
                        {{ __('Made with') }}
                        <i class="h-6 w-6 text-indigo-700" data-lucide="heart"></i>
                        {{ __('by Rentdo') }}
                    </div>
                </div>
            </div>
        </div>

        <button type="button" aria-label="Back to top" data-scroll-top
            class="fixed bottom-4 right-4 z-50 flex h-11 w-11 items-center justify-center rounded-full bg-white text-indigo-600 shadow-xl ring-1 ring-slate-200 transition hover:text-indigo-700 sm:bottom-8 sm:right-8 sm:h-12 sm:w-12">
            <svg viewBox="0 0 44 44" aria-hidden="true" class="absolute inset-0 h-full w-full -rotate-90">
                <circle cx="22" cy="22" r="20" class="fill-none stroke-slate-200" stroke-width="2"></circle>
                <circle cx="22" cy="22" r="20" class="fill-none stroke-indigo-600 transition-[stroke-dashoffset]"
                    stroke-width="2.5" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100"
                    data-scroll-progress></circle>
            </svg>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="relative h-5 w-5 fill-none stroke-current stroke-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 7-7 7 7"></path>
            </svg>
        </button>
    </footer>
    @endif

    {{-- Session flash + validation errors, handed to SweetAlert2 in app.js --}}
    @php
        $siteFlashType = session('success') ? 'success'
            : (session('error') ? 'error'
            : (session('warning') ? 'warning'
            : (session('info') || session('status') ? 'info' : null)));
        $siteFlashMessage = session('success') ?? session('error') ?? session('warning') ?? session('info') ?? session('status');
        $siteFlashPayload = [
            'flash' => $siteFlashType ? ['type' => $siteFlashType, 'message' => $siteFlashMessage] : null,
            'errors' => $errors->all(),
        ];
    @endphp
    @if ($siteFlashType || $errors->any())
        <script data-purpose="site-flash">
            window.siteFlash = @json($siteFlashPayload);
        </script>
    @endif

    @auth
        @if (setting('broadcast_driver') === 'pusher' && setting('pusher_key'))
            @php
                $broadcastConfigPayload = [
                    'key' => setting('pusher_key'),
                    'cluster' => setting('pusher_cluster', 'mt1'),
                    'authEndpoint' => url('/broadcasting/auth'),
                ];
            @endphp
            <script data-purpose="broadcast-config">
                window.broadcastConfig = @json($broadcastConfigPayload);
            </script>
        @endif
    @endauth

    @stack('scripts')
    @unless ($hasZoneInSession ?? false)
        <script data-purpose="zone-auto-detect">
            (function () {
                if (! navigator.geolocation) {
                    return;
                }

                function resolveZoneFromPosition() {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        fetch('{{ route('zone.resolve') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            }),
                        }).then(function (response) {
                            return response.json();
                        }).then(function (data) {
                            if (data.zone) {
                                window.location.reload();
                            }
                        }).catch(function () {});
                    }, function () {}, { timeout: 8000 });
                }

                // Defer the geolocation prompt until the visitor interacts with the page.
                // Requesting it on load triggers a permission prompt before any user
                // gesture, which browsers discourage and Lighthouse penalises.
                var interactionEvents = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
                function onFirstInteraction() {
                    interactionEvents.forEach(function (eventName) {
                        window.removeEventListener(eventName, onFirstInteraction);
                    });
                    resolveZoneFromPosition();
                }

                interactionEvents.forEach(function (eventName) {
                    window.addEventListener(eventName, onFirstInteraction, { once: true, passive: true });
                });
            })();
        </script>
    @endunless
    <script data-purpose="mobile-menu-toggle">
        const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
        const mobileMenu = document.querySelector('[data-mobile-menu]');
        const openIcon = document.querySelector('[data-mobile-menu-icon="open"]');
        const closeIcon = document.querySelector('[data-mobile-menu-icon="close"]');

        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                const isOpen = !mobileMenu.classList.contains('hidden');

                mobileMenu.classList.toggle('hidden', isOpen);
                openIcon?.classList.toggle('hidden', !isOpen);
                closeIcon?.classList.toggle('hidden', isOpen);
                mobileMenuToggle.setAttribute('aria-expanded', String(!isOpen));
            });
        }
    </script>
    <script data-purpose="mobile-submenu-toggle">
        document.querySelectorAll('[data-mobile-submenu-toggle]').forEach((trigger) => {
            const submenu = trigger.nextElementSibling;
            const icon = trigger.querySelector('[data-mobile-submenu-icon]');

            trigger.addEventListener('click', () => {
                const isOpen = !submenu.classList.contains('hidden');

                submenu.classList.toggle('hidden', isOpen);
                submenu.classList.toggle('flex', !isOpen);
                icon?.classList.toggle('rotate-180', !isOpen);
                trigger.setAttribute('aria-expanded', String(!isOpen));
            });
        });
    </script>
    <script data-purpose="scroll-to-top-progress">
        const scrollTopButton = document.querySelector('[data-scroll-top]');
        const scrollProgress = document.querySelector('[data-scroll-progress]');

        if (scrollTopButton && scrollProgress) {
            const updateScrollProgress = () => {
                const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
                const progress = scrollableHeight > 0 ? (window.scrollY / scrollableHeight) * 100 : 0;

                scrollProgress.style.strokeDashoffset = String(100 - Math.min(progress, 100));
            };

            scrollTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            });

            updateScrollProgress();
            window.addEventListener('scroll', updateScrollProgress, { passive: true });
            window.addEventListener('resize', updateScrollProgress);
        }
    </script>
</body>

</html>
