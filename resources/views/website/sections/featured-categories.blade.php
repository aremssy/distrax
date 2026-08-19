@php
    // Labels are translatable via __(); images are overridable per slot via
    // `home_image()` (Admin → Settings → Homepage).
    $categoryMeta = [
        'sale' => ['image' => 'assets/images/website/features/flat-1.webp', 'label' => 'For Sale'],
        'rent' => ['image' => 'assets/images/website/features/flat-2.webp', 'label' => 'For Rent'],
        'land' => ['image' => 'assets/images/website/flat/flat-1.webp', 'label' => 'Land & Commercial'],
        'hotel' => ['image' => 'assets/images/website/flat/flat-3.webp', 'label' => 'Short Stay'],
        'office' => ['image' => 'assets/images/website/features/office.webp', 'label' => 'Office Space'],
        'mess' => ['image' => 'assets/images/website/flat/flat-4.webp', 'label' => 'Mess / PG'],
    ];
@endphp

@if (($listingTypeCounts ?? collect())->isNotEmpty())
    <section class="py-20 px-4 md:px-6 max-w-7xl mx-auto" data-purpose="categories-section" data-reveal>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8" data-reveal-item>
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">{{ __('Browse by type') }}</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('Featured Categories') }}</h2>
            </div>
            <a class="inline-flex items-center gap-2 self-start text-sm font-semibold text-indigo-600 transition hover:text-slate-950 group"
                href="{{ route('properties.index') }}">
                {{ __('Explore All Categories') }}
                <i class="h-4 w-4 transition-transform group-hover:translate-x-1" data-lucide="arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-4 lg:grid-cols-5" data-reveal-children>
            @foreach ($categoryMeta as $type => $meta)
                @continue(($listingTypeCounts[$type] ?? 0) === 0)
                <a class="group block" href="{{ route('properties.index', ['type' => $type]) }}">
                    <div
                        class="h-full overflow-hidden rounded-2xl bg-white shadow-[0_10px_30px_rgba(15,23,42,0.06)] transition duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_18px_40px_rgba(15,23,42,0.12)]">
                        <img loading="lazy" decoding="async" alt="{{ __($meta['label']) }}" width="400" height="300"
                            class="aspect-4/3 w-full object-cover transition duration-500 group-hover:scale-105"
                            src="{{ home_image('home_category_'.$type.'_image', $meta['image']) }}">
                        <div class="px-3 pb-3 pt-4 text-center sm:px-4">
                            <div class="text-[15px] font-bold text-slate-950 sm:text-base">{{ __($meta['label']) }}</div>
                            <div class="mt-1 text-[13px] text-slate-600 sm:text-sm">{{ number_format($listingTypeCounts[$type]) }} {{ \Illuminate\Support\Str::plural('Property', $listingTypeCounts[$type]) }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
