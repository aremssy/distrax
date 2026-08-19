@extends('website.layouts.master')

@section('title', 'Browse Properties | '.config('app.name'))

@php
    $filterLabels = [
        'keyword' => 'Keyword', 'zone_id' => 'Location', 'type' => 'Type', 'min_price' => 'Min price',
        'max_price' => 'Max price', 'bedrooms' => 'Bedrooms', 'bathrooms' => 'Bathrooms',
        'furnished' => 'Furnished', 'parking' => 'Parking',
    ];
    $mapFields = ['sort', 'polygon', 'sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'];
    $activeFilters = collect($filters)->except($mapFields)->filter(fn ($value) => $value !== null && $value !== '');
    $hasAreaFilter = ! empty($polygon) || ! empty($filters['sw_lat'] ?? null);
    $googleMapsKey = setting('google_maps_api_key');
@endphp

@section('content')
    <main class="min-h-screen bg-slate-50 py-8 dark:bg-slate-950" data-purpose="properties-listing-page" x-data="{ filtersOpen: false }">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <nav class="mb-5 flex items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
                <a class="hover:text-indigo-600" href="{{ route('home') }}">Home</a>
                <i class="h-3.5 w-3.5" data-lucide="chevron-right" aria-hidden="true"></i>
                <span class="font-medium text-slate-950 dark:text-white">Properties</span>
            </nav>

            <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h1 class="text-3xl font-bold text-slate-950 dark:text-white">Find your next property</h1>
                    <p id="property-results-count" class="mt-2 text-sm text-slate-500 transition-opacity duration-200 dark:text-slate-400">
                        {{ trans_choice(':count active property|:count active properties', $listings->total(), ['count' => number_format($listings->total())]) }} found
                    </p>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div id="property-save-search" class="contents">
                    @auth
                        @if ($activeFilters->isNotEmpty())
                            <form action="{{ route('saved-searches.store') }}" method="POST">@csrf
                                <input type="hidden" name="name" value="{{ $filters['keyword'] ?? 'My property search' }}">
                                <input type="hidden" name="alert_on" value="1">
                                @foreach ($activeFilters as $name => $value)<input type="hidden" name="criteria[{{ $name }}]" value="{{ $value }}">@endforeach
                                <button class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-900 dark:bg-slate-900 dark:text-indigo-300" type="submit">Save search</button>
                            </form>
                        @endif
                    @endauth
                    </div>
                    <form action="{{ route('properties.index') }}" method="GET" data-live-search="#property-results-count,#property-active-filters,#property-save-search,#property-results">
                        @foreach (collect($filters)->except('sort') as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <span>Sort by</span>
                            <select name="sort" id="property-sort-select" data-live-param="sort"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 font-medium text-slate-800 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                                <option value="price_asc" @selected(($filters['sort'] ?? null) === 'price_asc')>Price: low to high</option>
                                <option value="price_desc" @selected(($filters['sort'] ?? null) === 'price_desc')>Price: high to low</option>
                                <option value="rating" @selected(($filters['sort'] ?? null) === 'rating')>Highest rated</option>
                                <option value="distance" @selected(($filters['sort'] ?? null) === 'distance')>Nearest to me</option>
                            </select>
                            <noscript><button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold" type="submit">Apply</button></noscript>
                        </label>
                    </form>
                    <button type="button" @click="filtersOpen = true"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 lg:hidden dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <i class="h-4 w-4" data-lucide="sliders-horizontal" aria-hidden="true"></i>Filters
                        @if ($activeFilters->isNotEmpty())
                            <span class="rounded-full bg-indigo-600 px-1.5 text-xs font-bold text-white">{{ $activeFilters->count() }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div id="property-active-filters" class="transition-opacity duration-200">
            @if ($activeFilters->isNotEmpty())
                <div class="mb-6 flex flex-wrap items-center gap-2">
                    @foreach ($activeFilters as $name => $value)
                        <a href="{{ route('properties.index', collect($filters)->except($name)->all()) }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-red-200 hover:text-red-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            {{ $filterLabels[$name] ?? ucfirst($name) }}: {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}
                            <i class="h-3 w-3" data-lucide="x" aria-hidden="true"></i>
                        </a>
                    @endforeach
                    @if ($hasAreaFilter)
                        <a href="{{ route('properties.index', collect($filters)->except($mapFields)->all()) }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-red-200 hover:text-red-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            Map search area
                            <i class="h-3 w-3" data-lucide="x" aria-hidden="true"></i>
                        </a>
                    @endif
                    <a href="{{ route('properties.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Clear all</a>
                </div>
            @endif
            </div>

            @if ($lowDataMode ?? false)
                <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400" aria-label="Property map">
                    Map view is hidden in low-data mode to save bandwidth.
                    <form class="inline" action="{{ route('low-data.toggle') }}" method="POST">@csrf<button class="font-semibold text-indigo-600 hover:underline" type="submit">Turn off low-data mode</button></form>
                </section>
            @elseif (! $googleMapsKey)
                <section class="mb-8 rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-slate-700 dark:bg-slate-900" aria-label="Property map">
                    <i class="mx-auto h-8 w-8 text-slate-400" data-lucide="map-pinned" aria-hidden="true"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ __('Map unavailable') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Google Maps has not been configured for this site.') }}</p>
                    @if (auth()->check() && auth()->user()->isAdmin())
                        <p class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Admin: add a Google Maps API key in Settings → General.') }}</p>
                    @endif
                </section>
            @else
                <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900" aria-label="Property map">
                    <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
                        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Map view</h2>
                        <button type="button" id="draw-area-toggle"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            Draw search area
                        </button>
                    </div>
                    <div id="properties-map" style="height: 380px;" aria-label="Interactive map of property results"></div>
                    <form id="draw-area-form" action="{{ route('properties.index') }}" method="GET" class="hidden">
                        @foreach (collect($filters)->except($mapFields) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="polygon" id="polygon-input">
                        <input type="hidden" name="sw_lat" id="sw-lat-input">
                        <input type="hidden" name="sw_lng" id="sw-lng-input">
                        <input type="hidden" name="ne_lat" id="ne-lat-input">
                        <input type="hidden" name="ne_lng" id="ne-lng-input">
                    </form>
                </section>
            @endif

            <div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
                {{-- Mobile filter drawer --}}
                <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
                    <div class="absolute inset-0 bg-slate-950/50" @click="filtersOpen = false"></div>
                    <div x-show="filtersOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="absolute inset-y-0 right-0 w-full max-w-sm overflow-y-auto bg-white p-5 shadow-xl dark:bg-slate-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">Filters</h2>
                            <button type="button" @click="filtersOpen = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close filters">
                                <i class="h-5 w-5" data-lucide="x" aria-hidden="true"></i>
                            </button>
                        </div>
                        @include('website.sections.partials.property-filters-form')
                    </div>
                </div>

                {{-- Desktop sidebar --}}
                <aside class="hidden lg:block">
                    <div class="sticky top-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        @include('website.sections.partials.property-filters-form')
                    </div>
                </aside>

                <section id="property-results" class="transition-opacity duration-200" aria-label="Property results">
                    @if ($listings->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                            <i class="mx-auto h-10 w-10 text-slate-500" data-lucide="search-x" aria-hidden="true"></i>
                            <h2 class="mt-4 text-xl font-bold text-slate-950 dark:text-white">No properties match these filters</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">Try widening your price range, changing the location, or removing a filter.</p>
                            <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" href="{{ route('properties.index') }}">Clear all filters</a>

                            @if (isset($nearbyZones) && $nearbyZones->isNotEmpty())
                                <div class="mx-auto mt-8 max-w-md text-left">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Try a nearby area instead</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($nearbyZones as $nearbyZone)
                                            <a href="{{ route('properties.index', array_merge(collect($filters)->except(['zone_id', 'sort'])->all(), ['zone_id' => $nearbyZone->id])) }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900">
                                                {{ $nearbyZone->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @auth
                                <div class="mx-auto mt-6 max-w-md">
                                    <form action="{{ route('saved-searches.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $filters['keyword'] ?? 'My property search' }}">
                                        <input type="hidden" name="alert_on" value="1">
                                        @foreach (collect($filters)->except(['sort'])->filter() as $name => $value)
                                            <input type="hidden" name="criteria[{{ $name }}]" value="{{ $value }}">
                                        @endforeach
                                        <button type="submit" class="text-sm font-semibold text-indigo-600 hover:underline">Save this search and get notified when a match appears</button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($listings as $listing)
                                <x-website.property-card :$listing />
                            @endforeach
                        </div>
                        <div class="mt-10">{{ $listings->links() }}</div>
                    @endif
                </section>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
    (function () {
        const select = document.getElementById('property-sort-select');
        if (!select) return;

        select.addEventListener('change', function (event) {
            if (select.value !== 'distance') return;

            const url = new URL(window.location.href);
            if (url.searchParams.get('near_lat') && url.searchParams.get('near_lng')) return;

            event.stopImmediatePropagation();
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    url.searchParams.set('sort', 'distance');
                    url.searchParams.set('near_lat', position.coords.latitude);
                    url.searchParams.set('near_lng', position.coords.longitude);
                    url.searchParams.delete('page');
                    window.location.assign(url);
                },
                () => { window.location.assign(url); },
                { timeout: 8000 },
            );
        });
    })();
    </script>
@endpush

@if (! ($lowDataMode ?? false) && $googleMapsKey)
@push('scripts')
    @php
        $mapMarkers = collect($listings->items())
            ->filter(fn ($listing) => $listing->lat !== null && $listing->lng !== null)
            ->map(fn ($listing) => [
                'lat' => (float) $listing->lat,
                'lng' => (float) $listing->lng,
                'title' => $listing->title,
                'url' => route('properties.show', $listing),
            ])
            ->values();
    @endphp
    <script data-purpose="properties-map-init">
        function initPropertiesMap() {
            const mapEl = document.getElementById('properties-map');
            if (!mapEl || !window.google?.maps) {
                return;
            }

            const mapListings = @json($mapMarkers);
            const existingPolygon = @json($polygon ?? null);

            const defaultCenter = mapListings.length
                ? { lat: mapListings[0].lat, lng: mapListings[0].lng }
                : { lat: 23.685, lng: 90.356 };
            const map = new google.maps.Map(mapEl, {
                center: defaultCenter,
                zoom: mapListings.length ? 12 : 6,
            });

            const infoWindow = new google.maps.InfoWindow();

            mapListings.forEach(function (item) {
                const marker = new google.maps.Marker({
                    position: { lat: item.lat, lng: item.lng },
                    map: map,
                    title: item.title,
                });

                marker.addListener('click', function () {
                    infoWindow.setContent('<a href="' + item.url + '" class="font-semibold">' + item.title + '</a>');
                    infoWindow.open({ anchor: marker, map: map });
                });
            });

            if (existingPolygon) {
                new google.maps.Polygon({
                    paths: existingPolygon.map(function (p) { return { lat: p[0], lng: p[1] }; }),
                    strokeColor: '#4f46e5',
                    fillColor: '#4f46e5',
                    map: map,
                }).setMap(map);
            }

            let drawing = false;
            let points = [];
            let drawnPolygon = null;

            const toggleButton = document.getElementById('draw-area-toggle');

            function resetDrawing() {
                drawing = false;
                points = [];
                if (drawnPolygon) {
                    drawnPolygon.setMap(null);
                    drawnPolygon = null;
                }
                toggleButton.textContent = 'Draw search area';
            }

            function finishDrawing() {
                if (points.length < 3) {
                    resetDrawing();
                    return;
                }

                const lats = points.map(function (p) { return p[0]; });
                const lngs = points.map(function (p) { return p[1]; });

                document.getElementById('polygon-input').value = JSON.stringify(points);
                document.getElementById('sw-lat-input').value = Math.min.apply(null, lats);
                document.getElementById('sw-lng-input').value = Math.min.apply(null, lngs);
                document.getElementById('ne-lat-input').value = Math.max.apply(null, lats);
                document.getElementById('ne-lng-input').value = Math.max.apply(null, lngs);

                document.getElementById('draw-area-form').submit();
            }

            toggleButton.addEventListener('click', function () {
                if (drawing) {
                    finishDrawing();
                    return;
                }

                resetDrawing();
                drawing = true;
                toggleButton.textContent = 'Finish area (0)';
            });

            map.addListener('click', function (event) {
                if (!drawing) {
                    return;
                }

                points.push([event.latLng.lat(), event.latLng.lng()]);
                if (drawnPolygon) {
                    drawnPolygon.setMap(null);
                }
                drawnPolygon = new google.maps.Polygon({
                    paths: points.map(function (p) { return { lat: p[0], lng: p[1] }; }),
                    strokeColor: '#4f46e5',
                    fillColor: '#4f46e5',
                    map: map,
                });
                toggleButton.textContent = 'Finish area (' + points.length + ')';
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&callback=initPropertiesMap&loading=async" async defer></script>
@endpush
@endif
