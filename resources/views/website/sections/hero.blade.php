@php
    $heroLcpImage = collect($heroSlides)->first()['image'] ?? null;
@endphp
@if ($heroLcpImage)
    @push('head')
        <link rel="preload" as="image" href="{{ $heroLcpImage }}" fetchpriority="high">
    @endpush
@endif

@push('head')
    <style>
        /*
         * Until Swiper.js initialises (it adds `.swiper-initialized`), the slides are
         * still stacked in normal flow, so the wrapper is ~3× a single slide tall and
         * everything below it jumps up when the carousel collapses to one slide. Keeping
         * only the first slide visible pre-init reserves the correct height and removes
         * the layout shift (Lighthouse CLS).
         */
        .hero-swiper:not(.swiper-initialized) .swiper-slide + .swiper-slide {
            display: none;
        }
    </style>
@endpush

<section class="relative bg-white" data-purpose="hero-section">
    <div class="hero-swiper swiper relative" id="hero-swiper">
        <button type="button" aria-label="Previous slide"
            class="hero-nav-prev absolute left-4 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-md transition hover:bg-slate-50 md:left-8 md:flex">
            <i class="h-5 w-5" data-lucide="chevron-left"></i>
        </button>
        <button type="button" aria-label="Next slide"
            class="hero-nav-next absolute right-4 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-md transition hover:bg-slate-50 md:right-8 md:flex">
            <i class="h-5 w-5" data-lucide="chevron-right"></i>
        </button>

        <div class="swiper-wrapper">
            @foreach ($heroSlides as $slide)
                <div class="swiper-slide">
                    <div class="relative flex min-h-[560px] items-center overflow-hidden md:min-h-[620px]">
                        <div class="absolute inset-y-0 right-0 hidden w-full md:block md:w-1/2 lg:w-3/5">
                            <img alt="" class="h-full w-full object-cover" decoding="async" width="1280" height="960"
                                fetchpriority="{{ $loop->first ? 'high' : 'low' }}"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                src="{{ $slide['image'] }}">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 w-4/5 bg-[linear-gradient(to_right,#fff_0%,#fff_20%,rgba(255,255,255,0.85)_45%,rgba(255,255,255,0.45)_70%,rgba(255,255,255,0)_100%)]">
                            </div>
                        </div>

                        <div class="relative z-10 w-full max-w-7xl mx-auto px-4 pt-16 pb-72 md:px-6 md:py-20">
                            <div class="hero-slide-content max-w-3xl pt-10 md:pt-6">
                                <h1 class="hero-slide-title mb-6 font-bold leading-tight text-slate-950" style="font-size: clamp(1.75rem, 1rem + 3.1vw, 3.5rem);">
                                    <span class="block md:whitespace-nowrap">{{ $slide['title'] }}</span>
                                    <span class="block text-indigo-700 md:whitespace-nowrap">{{ $slide['highlight'] }}</span>
                                </h1>
                                <p class="hero-slide-subtitle mb-8 max-w-md text-lg text-slate-600">
                                    {{ $slide['subtitle'] }}</p>
                                <a href="{{ route('properties.index') }}"
                                    class="hero-slide-cta inline-flex items-center gap-3 rounded-lg bg-indigo-950 px-8 py-4 font-bold text-white transition hover:bg-indigo-900">
                                    {{ __('Explore Properties') }}
                                    <i class="h-4 w-4" data-lucide="arrow-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 h-72 md:hidden">
                            <img alt="" class="h-full w-full object-cover" decoding="async" width="1280" height="960"
                                fetchpriority="{{ $loop->first ? 'high' : 'low' }}"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                src="{{ $slide['image'] }}">
                            <div
                                class="absolute inset-x-0 top-0 h-24 bg-[linear-gradient(to_bottom,#fff_0%,#fff_20%,rgba(255,255,255,0.85)_40%,rgba(255,255,255,0.45)_65%,rgba(255,255,255,0)_100%)]">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="relative z-20 mx-auto -mt-12 max-w-7xl px-4 md:-mt-10 md:px-6">
        <form action="{{ route('properties.index') }}" method="GET" id="hero-search-form"
            data-purpose="homepage-search"
            class="overflow-visible rounded-2xl border border-slate-200/80 bg-white shadow-[0_12px_35px_rgba(15,23,42,0.12)]"
            x-data="{
                open: null,
                type: '',
                cityId: '', cityLabel: 'All Locations', cityQuery: '',
                areaId: '', areaLabel: 'Any Area', areaQuery: '',
                get zoneId() { return this.cityId || this.areaId },
                priceMin: '', priceMax: '', priceLabel: 'Any Price',
                toggle(name) { this.open = this.open === name ? null : name },
                selectType(value) { this.type = value },
                selectCity(id, label) { this.cityId = id; this.cityLabel = label; this.areaId = ''; this.areaLabel = 'Any Area'; this.open = null; this.cityQuery = '' },
                selectArea(id, label) { this.areaId = id; this.areaLabel = label; this.cityId = ''; this.cityLabel = 'All Locations'; this.open = null; this.areaQuery = '' },
                selectPrice(min, max, label) { this.priceMin = min; this.priceMax = max; this.priceLabel = label; this.open = null },
            }"
            @click.outside="open = null" @keydown.escape.window="open = null">
            <input type="hidden" name="type" :value="type">
            <input type="hidden" name="zone_id" :value="zoneId">
            <input type="hidden" name="min_price" :value="priceMin">
            <input type="hidden" name="max_price" :value="priceMax">

            <div class="flex items-center gap-1 overflow-x-auto rounded-t-2xl border-b border-slate-100 bg-slate-50/80 p-2"
                role="tablist" aria-label="Property type">
                <button type="button" @click="selectType('')" role="tab" :aria-selected="type === ''"
                    class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition"
                    :class="type === '' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-indigo-600'">
                    All
                </button>
                @foreach ($heroPropertyTypes as $propertyType)
                    <button type="button" @click="selectType({{ Js::from($propertyType['value']) }})" role="tab"
                        :aria-selected="type === {{ Js::from($propertyType['value']) }}"
                        class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="type === {{ Js::from($propertyType['value']) }} ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-indigo-600'">
                        {{ $propertyType['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 p-2 sm:grid-cols-3 lg:grid-cols-[1fr_1fr_1fr_auto]">
                <div class="relative border-b border-slate-100 sm:border-b-0 sm:border-r">
                    <button type="button" @click="toggle('city')" aria-haspopup="listbox" :aria-expanded="open === 'city'"
                        class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition-colors hover:bg-slate-50">
                        <i class="h-4.5 w-4.5 shrink-0 text-indigo-500" data-lucide="map-pin" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Location') }}</span>
                            <span class="block truncate text-[15px] font-semibold text-slate-900" x-text="cityLabel"></span>
                        </div>
                        <i class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
                            data-lucide="chevron-down" :class="open === 'city' && 'rotate-180'"></i>
                    </button>
                    <div x-show="open === 'city'" x-cloak x-transition.origin.top
                        class="absolute left-0 top-full z-30 mt-2 w-full min-w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input type="search" x-model="cityQuery" placeholder="Search city…" aria-label="Search city"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                        <div class="max-h-64 overflow-y-auto py-1.5">
                            <button type="button" @click="selectCity('', 'All Locations')"
                                class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                :class="cityId === '' ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">
                                All Locations</button>
                            @foreach ($heroCities as $city)
                                <button type="button" @click="selectCity({{ Js::from((string) $city->id) }}, {{ Js::from($city->name) }})"
                                    x-show="cityQuery === '' || {{ Js::from(Str::lower($city->name)) }}.includes(cityQuery.toLowerCase())"
                                    class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                    :class="cityId === {{ Js::from((string) $city->id) }} ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">{{ $city->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="relative border-b border-slate-100 sm:border-b-0 sm:border-r">
                    <button type="button" @click="toggle('area')" aria-haspopup="listbox" :aria-expanded="open === 'area'"
                        class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition-colors hover:bg-slate-50">
                        <i class="h-4.5 w-4.5 shrink-0 text-indigo-500" data-lucide="locate" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Area') }}</span>
                            <span class="block truncate text-[15px] font-semibold text-slate-900" x-text="areaLabel"></span>
                        </div>
                        <i class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
                            data-lucide="chevron-down" :class="open === 'area' && 'rotate-180'"></i>
                    </button>
                    <div x-show="open === 'area'" x-cloak x-transition.origin.top
                        class="absolute left-0 top-full z-30 mt-2 w-full min-w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input type="search" x-model="areaQuery" placeholder="Search area…" aria-label="Search area"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </div>
                        <div class="max-h-64 overflow-y-auto py-1.5">
                            <button type="button" @click="selectArea('', 'Any Area')"
                                class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                :class="areaId === '' ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">
                                Any Area</button>
                            @foreach ($heroAreas as $area)
                                <button type="button" @click="selectArea({{ Js::from((string) $area->id) }}, {{ Js::from($area->name) }})"
                                    x-show="areaQuery === '' || {{ Js::from(Str::lower($area->name)) }}.includes(areaQuery.toLowerCase())"
                                    class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                    :class="areaId === {{ Js::from((string) $area->id) }} ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">{{ $area->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="relative border-b border-slate-100 sm:border-b-0">
                    <button type="button" @click="toggle('price')" aria-haspopup="listbox" :aria-expanded="open === 'price'"
                        class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition-colors hover:bg-slate-50">
                        <i class="h-4.5 w-4.5 shrink-0 text-indigo-500" data-lucide="banknote" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Price Range') }}</span>
                            <span class="block truncate text-[15px] font-semibold text-slate-900" x-text="priceLabel"></span>
                        </div>
                        <i class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
                            data-lucide="chevron-down" :class="open === 'price' && 'rotate-180'"></i>
                    </button>
                    <div x-show="open === 'price'" x-cloak x-transition.origin.top
                        class="absolute left-0 top-full z-30 mt-2 w-full min-w-64 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg lg:left-auto lg:right-0">
                        <button type="button" @click="selectPrice('', '', 'Any Price')"
                            class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                            :class="priceMin === '' && priceMax === '' ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">
                            Any Price</button>
                        @foreach ($heroPriceRanges as $range)
                            <button type="button"
                                @click="selectPrice({{ Js::from((string) ($range['min'] ?? '')) }}, {{ Js::from((string) ($range['max'] ?? '')) }}, {{ Js::from($range['label']) }})"
                                class="block w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                :class="priceMin === {{ Js::from((string) ($range['min'] ?? '')) }} && priceMax === {{ Js::from((string) ($range['max'] ?? '')) }} ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700'">{{ $range['label'] }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-stretch p-1.5 sm:col-span-3 lg:col-span-1 lg:ps-3">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-7 py-3.5 font-bold text-white transition-all duration-200 hover:bg-indigo-700 hover:shadow-lg active:scale-[0.98]">
                        <i class="h-4 w-4" data-lucide="search"></i>
                        {{ __('Search Properties') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <div data-purpose="homepage-statistics"
            class="grid grid-cols-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_8px_30px_rgba(15,23,42,0.05)] sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($homepageStatistics as $statistic)
                <div data-purpose="homepage-statistic"
                    class="group flex items-start gap-4 border-b border-slate-200 p-6 last:border-b-0 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 lg:border-b-0 lg:border-r lg:last:border-r-0">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 transition-colors duration-200 group-hover:bg-indigo-100">
                        <i class="h-6 w-6" data-lucide="{{ $statistic['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-2xl font-bold leading-none text-slate-950">{{ $statistic['value'] }}</div>
                        <div class="mt-2 text-[15px] font-semibold text-slate-900">{{ $statistic['title'] }}</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $statistic['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
