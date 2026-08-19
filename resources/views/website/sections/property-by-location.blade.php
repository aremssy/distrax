<section class="py-16 sm:py-20" data-purpose="property-by-location" data-reveal>
    <div class="mx-auto max-w-7xl px-4 md:px-6">
        <div class="mb-9 flex flex-col justify-between gap-5 sm:flex-row sm:items-center" data-reveal-item>
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border-4 border-white bg-indigo-50 text-indigo-600 shadow-[0_8px_24px_rgba(79,70,229,0.12)] ring-1 ring-indigo-100">
                    <i class="h-7 w-7" data-lucide="map-pin" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('Property by Location') }}</h2>
                    <p class="mt-2 text-base text-slate-600">{{ __('Explore active properties in popular areas.') }}</p>
                </div>
            </div>
            <a class="inline-flex items-center gap-2 self-start text-sm font-semibold text-indigo-600 transition hover:gap-3 hover:text-indigo-700 sm:self-auto"
                href="{{ route('properties.index') }}">
                {{ __('View all locations') }}
                <i class="h-5 w-5" data-lucide="arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        @if ($popularZones->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-base text-slate-600">
                {{ __('Locations will appear here as soon as active property listings are available.') }}
            </p>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-children>
                @foreach ($popularZones as $zone)
                    <a class="group relative flex min-h-56 flex-col overflow-hidden rounded-2xl border border-indigo-100/90 bg-white p-6 shadow-[0_8px_24px_rgba(30,41,59,0.06)] transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-[0_16px_36px_rgba(79,70,229,0.12)]"
                        data-purpose="location-card" href="{{ route('properties.index', ['zone_id' => $zone->id]) }}">
                        <div class="relative z-10 flex items-start gap-4">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">
                                <i class="h-6 w-6" data-lucide="map-pin" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 pt-1">
                                <span class="block text-lg font-bold text-slate-950">{{ $zone->name }}</span>
                                <span class="mt-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $zone->type }}</span>
                            </span>
                        </div>

                        <img loading="lazy" decoding="async" width="288" height="216" class="pointer-events-none absolute bottom-0 right-0 w-[76%] max-w-72 opacity-55"
                            src="{{ home_image('home_location_bg_image', 'assets/images/website/location/location-card-bg.webp') }}" alt=""
                            aria-hidden="true">

                        <div class="relative z-10 mt-auto flex items-end justify-between gap-4 pt-10">
                            <span class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-600">
                                {{ trans_choice(':count property|:count properties', $zone->listings_count, ['count' => number_format($zone->listings_count)]) }}
                            </span>
                            <span class="text-indigo-600 transition group-hover:translate-x-1">
                                <i class="h-6 w-6" data-lucide="arrow-right" aria-hidden="true"></i>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
