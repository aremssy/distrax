<section class="py-20" data-purpose="featured-properties" data-reveal>
    <div class="mx-auto max-w-7xl px-4 md:px-6">
        <div class="mb-10 flex flex-col justify-between gap-4 md:flex-row md:items-end" data-reveal-item>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white">{{ __('Featured Properties') }}</h2>
                <div class="mt-3 h-1 w-16 rounded bg-indigo-600"></div>
            </div>
            <a class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                href="{{ route('properties.index', ['featured' => 1]) }}">
                {{ __('View all properties') }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        @if ($featuredListings->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('New properties are coming soon') }}</h3>
                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">{{ __('Browse all available properties or check back shortly.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4" data-reveal-children>
                @foreach ($featuredListings as $listing)
                    <x-website.property-card :$listing />
                @endforeach
            </div>
        @endif
    </div>
</section>
