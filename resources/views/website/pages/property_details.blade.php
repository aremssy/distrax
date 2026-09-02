@extends('website.layouts.master')

@section('title', $listing->title.' | '.config('app.name'))

@php
    $showListingMap = $listing->lat && $listing->lng && ! ($lowDataMode ?? false);

    // Google's `output=embed` endpoint needs no API key. If one is configured we use the
    // official Embed API instead, which renders without the "for development" watermark.
    $googleMapsKey = setting('google_maps_api_key');
    $listingMapSrc = $showListingMap
        ? ($googleMapsKey
            ? 'https://www.google.com/maps/embed/v1/place?key='.urlencode($googleMapsKey).'&q='.$listing->lat.','.$listing->lng.'&zoom=16'
            : 'https://www.google.com/maps?q='.$listing->lat.','.$listing->lng.'&z=16&hl='.app()->getLocale().'&output=embed')
        : null;
@endphp

@push('head')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($listing->description ?? $listing->title), 155) }}">
    <link rel="canonical" href="{{ route('properties.show', $listing) }}">
    {{-- JSON_HEX_TAG escapes < and >, so a user-authored title cannot close this script tag. --}}
    <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    @php
        $mediaUrl = static function ($media): string {
            if ($media->disk === 'remote' || \Illuminate\Support\Str::startsWith($media->path, ['http://', 'https://'])) {
                return $media->path;
            }

            return \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->path);
        };

        $images = $listing->galleryImages;
        $mainImage = $images->firstWhere('is_cover', true) ?? $images->first();

        // Low-data mode keeps only the cover image, which also empties the
        // secondary thumbnails and the lightbox gallery below.
        if (($lowDataMode ?? false) && $mainImage) {
            $images = collect([$mainImage]);
        }

        $ordinal = static fn (int $number): string => $number.match (true) {
            in_array($number % 100, [11, 12, 13], true) => 'th',
            $number % 10 === 1 => 'st',
            $number % 10 === 2 => 'nd',
            $number % 10 === 3 => 'rd',
            default => 'th',
        };

        $galleryUrls = $images->map($mediaUrl)->values();
        $thumbnails = $images->where('id', '!=', $mainImage?->id)->take(4);
        $remainingPhotos = max(0, $images->count() - 1 - $thumbnails->count());

        $floorPlans = ($lowDataMode ?? false) ? collect() : $listing->floorPlanImages;
        $priceSuffix = \App\Enums\PropertyType::tryFrom($listing->type)?->priceSuffix();
        // Amenity keys are stored on the listing; labels and icons come from config/amenities.php.
        $amenityConfig = config('amenities');
        $amenities = collect($listing->utility_flags ?? [])
            ->filter()
            ->map(fn (string $key): array => [
                'label' => $amenityConfig[$key]['label'] ?? str($key)->replace('_', ' ')->title()->value(),
                'icon' => $amenityConfig[$key]['icon'] ?? 'circle-check',
            ])
            ->values();

        // Kitchen/balcony (and any other admin-defined stat) come from custom fields.
        $customValues = $listing->customFieldValues
            ->filter(fn ($value) => $value->field && filled($value->value))
            ->mapWithKeys(fn ($value) => [$value->field->key => ['label' => $value->field->label, 'value' => $value->value]]);

        $stats = collect([
            ['icon' => 'bed-double', 'label' => __('Bedrooms'), 'value' => $listing->bedrooms],
            ['icon' => 'bath', 'label' => __('Bathrooms'), 'value' => $listing->bathrooms],
            ['icon' => 'cooking-pot', 'label' => __('Kitchen'), 'value' => $customValues['kitchen']['value'] ?? null],
            ['icon' => 'fence', 'label' => __('Balcony'), 'value' => $customValues['balcony']['value'] ?? null],
            ['icon' => 'building-2', 'label' => __('Floor'), 'value' => $listing->floor ? $ordinal($listing->floor) : null],
            ['icon' => 'maximize', 'label' => __('Sq Ft'), 'value' => $listing->area_sqft ? number_format($listing->area_sqft) : null],
        ])->filter(fn (array $stat): bool => filled($stat['value']))->values();

        $purposeLabels = ['sale' => __('For Sale'), 'rent' => __('For Rent'), 'hotel' => __('Short Stay'), 'mess' => __('Mess / PG'), 'land' => __('Land')];
        $shareUrl = route('properties.show', $listing);
        $shareLinks = [
            ['label' => __('Share on Facebook'), 'icon' => 'facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl)],
            ['label' => __('Share on X'), 'icon' => 'twitter', 'url' => 'https://twitter.com/intent/tweet?url='.urlencode($shareUrl).'&text='.urlencode($listing->title)],
            ['label' => __('Share on LinkedIn'), 'icon' => 'linkedin', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url='.urlencode($shareUrl)],
        ];

        // In-page jump navigation. Each entry maps to a section id in the left stack;
        // conditional entries drop out when the listing has no data for them.
        $navSections = collect([
            ['id' => 'overview', 'label' => __('Overview'), 'when' => true],
            ['id' => 'verification', 'label' => __('Verification'), 'when' => true],
            ['id' => 'deal-analysis', 'label' => __('Deal Analysis'), 'when' => $dealScore || $valuation || $comparables->isNotEmpty()],
            ['id' => 'risk-snapshot', 'label' => __('Risk'), 'when' => $riskAssessments->isNotEmpty()],
            ['id' => 'description', 'label' => __('Description'), 'when' => true],
            ['id' => 'details', 'label' => __('Details'), 'when' => true],
            ['id' => 'amenities', 'label' => __('Amenities'), 'when' => $amenities->isNotEmpty()],
            ['id' => 'location', 'label' => __('Location'), 'when' => (bool) ($listing->lat && $listing->lng)],
            ['id' => 'comparables', 'label' => __('Comparables'), 'when' => $comparables->isNotEmpty()],
            ['id' => 'reviews', 'label' => __('Reviews'), 'when' => true],
        ])->filter(fn (array $section): bool => $section['when'])->values();
    @endphp

    <main class="min-h-screen bg-slate-50 pb-24 pt-6 lg:pb-10" data-purpose="property-details-page"
        x-data="{ lightboxOpen: false, activeImage: 0, gallery: {{ $galleryUrls->toJson() }}, reportOpen: false }">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <div class="mb-6 flex flex-col justify-between gap-3 text-sm md:flex-row md:items-center">
                <nav class="flex flex-wrap items-center gap-2 text-slate-600" aria-label="Breadcrumb">
                    <a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a>
                    <i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right" aria-hidden="true"></i>
                    <a class="transition hover:text-indigo-600" href="{{ route('properties.index', ['type' => $listing->type]) }}">{{ __('Properties') }}</a>
                    <i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right" aria-hidden="true"></i>
                    <span class="max-w-64 truncate font-semibold text-slate-950">{{ $listing->title }}</span>
                </nav>
                <a class="inline-flex items-center gap-1.5 font-medium text-slate-600 transition hover:text-indigo-600" href="{{ route('properties.index') }}">
                    <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i> {{ __('Back to listings') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1.9fr)_minmax(320px,1fr)] lg:items-start">
                {{-- ── Left column ─────────────────────────────────────────────── --}}
                <div class="space-y-6">
                    <section aria-label="Property gallery">
                        <div class="relative overflow-hidden rounded-2xl bg-slate-200">
                            @if ($mainImage)
                                <button type="button" class="block aspect-[16/10] w-full cursor-zoom-in"
                                    @click="activeImage = 0; lightboxOpen = true" aria-label="Open gallery">
                                    <img class="h-full w-full object-cover object-center" src="{{ $mediaUrl($mainImage) }}"
                                        alt="{{ $listing->title }}" width="960" height="600">
                                </button>

                                @if ($listing->is_featured)
                                    <span class="absolute bottom-4 start-4 rounded-lg bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">{{ __('Featured') }}</span>
                                @endif

                                <div class="absolute end-4 top-4">
                                    @auth
                                        <form method="POST" data-purpose="detail-favorite-toggle"
                                            action="{{ $isFavorited ? route('properties.favorite.destroy', $listing) : route('properties.favorite.store', $listing) }}">
                                            @csrf
                                            @if ($isFavorited) @method('DELETE') @endif
                                            <button type="submit" aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
                                                aria-label="{{ $isFavorited ? __('Remove from favorites') : __('Save this property') }}"
                                                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/95 shadow-md backdrop-blur transition hover:scale-110 {{ $isFavorited ? 'text-rose-500' : 'text-slate-600 hover:text-rose-500' }}">
                                                <i class="h-5 w-5 {{ $isFavorited ? 'fill-current' : '' }}" data-lucide="heart" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login', ['intended' => request()->fullUrl()]) }}" aria-label="{{ __('Log in to save this property') }}"
                                            class="flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-slate-600 shadow-md backdrop-blur transition hover:scale-110 hover:text-rose-500">
                                            <i class="h-5 w-5" data-lucide="heart" aria-hidden="true"></i>
                                        </a>
                                    @endauth
                                </div>
                            @else
                                <div class="flex aspect-[16/10] items-center justify-center text-center text-slate-500">
                                    <div><i class="mx-auto h-10 w-10" data-lucide="image" aria-hidden="true"></i><p class="mt-3 text-sm">{{ __('Photos are coming soon') }}</p></div>
                                </div>
                            @endif
                        </div>

                        @if ($thumbnails->isNotEmpty())
                            <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                                @foreach ($thumbnails as $index => $thumbnail)
                                    <button type="button" @click="activeImage = {{ $index + 1 }}; lightboxOpen = true"
                                        class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-200 transition hover:opacity-90"
                                        aria-label="{{ __('Open photo :number', ['number' => $index + 2]) }}">
                                        <img class="h-full w-full object-cover" src="{{ $mediaUrl($thumbnail) }}"
                                            alt="{{ __(':title photo :number', ['title' => $listing->title, 'number' => $index + 2]) }}" loading="lazy" width="240" height="180">
                                    </button>
                                @endforeach

                                @if ($remainingPhotos > 0)
                                    <button type="button" @click="activeImage = 0; lightboxOpen = true"
                                        class="flex aspect-[4/3] flex-col items-center justify-center rounded-xl bg-slate-900 text-white transition hover:bg-slate-800">
                                        <span class="text-lg font-bold">+{{ $remainingPhotos }}</span>
                                        <span class="text-xs">{{ __('Photos') }}</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </section>

                    {{-- Title, purpose, price and address --}}
                    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                                {{ $purposeLabels[$listing->type] ?? ucfirst($listing->type) }}
                            </span>
                            <span class="flex items-baseline gap-1 rounded-full bg-slate-950 px-3 py-1 text-sm font-bold tracking-tight text-white sm:text-base">
                                {{ moneyFrom($listing->price, $listing->currency_code) }}
                                @if ($priceSuffix)<span class="text-xs font-medium text-slate-300">{{ $priceSuffix }}</span>@endif
                            </span>
                            <x-verification-badge :status="$listing->verificationCase?->status ?? 'in_progress'" />
                        </div>

                        <h1 class="mt-3 text-xl font-bold leading-snug tracking-tight text-slate-950 sm:text-2xl">{{ $listing->title }}</h1>
                        <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600">
                            <i class="h-4 w-4 shrink-0 text-indigo-500" data-lucide="map-pin" aria-hidden="true"></i>
                            <span class="line-clamp-2">{{ $listing->address ?? $listing->zone?->name }}</span>
                        </p>
                    </header>

                    {{-- Sticky in-page navigation. Sits just below the site header (sticky top-0). --}}
                    <nav class="sticky top-20 z-30 flex gap-1 overflow-x-auto rounded-2xl border border-slate-200 bg-white/95 px-2 py-1.5 shadow-sm backdrop-blur"
                        aria-label="Property sections"
                        x-data="{
                            active: '{{ $navSections->first()['id'] ?? 'overview' }}',
                            init() {
                                const observer = new IntersectionObserver((entries) => {
                                    entries.forEach((entry) => { if (entry.isIntersecting) { this.active = entry.target.id; } });
                                }, { rootMargin: '-45% 0px -50% 0px' });
                                @foreach ($navSections as $section)
                                    { const el = document.getElementById('{{ $section['id'] }}'); if (el) { observer.observe(el); } }
                                @endforeach
                            }
                        }">
                        @foreach ($navSections as $section)
                            <a href="#{{ $section['id'] }}" @click="active = '{{ $section['id'] }}'"
                                class="shrink-0 rounded-xl px-4 py-2 text-sm font-semibold transition"
                                :class="active === '{{ $section['id'] }}' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'">
                                {{ $section['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Overview: highlight facts --}}
                    <section id="overview" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="h-4.5 w-4.5" data-lucide="layout-grid" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Overview') }}</h2>
                        </header>
                        @if ($stats->isNotEmpty())
                            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($stats as $stat)
                                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm">
                                            <i class="h-4.5 w-4.5" data-lucide="{{ $stat['icon'] }}" aria-hidden="true"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <dd class="text-sm font-bold text-slate-950">{{ $stat['value'] }}</dd>
                                            <dt class="text-xs text-slate-500">{{ $stat['label'] }}</dt>
                                        </div>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-4 text-sm text-slate-600">{{ __('No highlights specified for this property.') }}</p>
                        @endif
                    </section>

                    {{-- Verification & Disclosures --}}
                    <section id="verification" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="h-4.5 w-4.5" data-lucide="shield-check" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Verification & Disclosures') }}</h2>
                        </header>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <x-verification-badge :status="$listing->verificationCase?->status ?? 'in_progress'" />
                            @if ($listing->verificationCase?->scores->last())
                                <a href="{{ route('verify.show', $listing->verificationCase->scores->last()->reference_id) }}"
                                   class="text-sm font-semibold text-indigo-600 hover:underline">{{ __('View verification passport') }}</a>
                            @endif
                        </div>

                        @php
                            $saleReason = match ($listing->distress_reason_visibility) {
                                'public' => $listing->distress_reason_category,
                                'disclosure_only' => auth()->check() ? $listing->distress_reason_category : null,
                                default => null,
                            };
                        @endphp
                        @if ($saleReason)
                            <p class="mt-4 text-sm text-slate-600">
                                <span class="font-semibold text-slate-800">{{ __('Reason for sale') }}:</span> {{ ucwords(str_replace('_', ' ', $saleReason)) }}
                            </p>
                        @endif

                        @if ($listing->disclosures->isNotEmpty())
                            <div class="mt-5">
                                <h3 class="text-sm font-bold text-slate-800">{{ __('Disclosures') }}</h3>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($listing->disclosures as $disclosure)
                                        <li class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                            <span class="font-semibold">{{ ucwords(str_replace('_', ' ', $disclosure->category)) }}:</span> {{ $disclosure->description }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($isOwner && $documents->isNotEmpty())
                            <div class="mt-5">
                                <h3 class="text-sm font-bold text-slate-800">{{ __('Document Vault (owner only)') }}</h3>
                                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                    @foreach ($documents as $document)
                                        <li>{{ ucwords(str_replace('_', ' ', $document->type)) }} &mdash; {{ $document->is_verified ? __('Verified') : __('Pending review') }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>

                    {{-- ── Deal Score & market price block (blueprint 3.6) ─────────── --}}
                    @if ($dealScore || $marketValue)
                    <section aria-label="Deal Score and price analysis" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        @if ($dealScore)
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Distrax Deal Score') }}</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        <span class="text-3xl font-extrabold text-slate-950">{{ $dealScore->score }}<span class="text-lg text-slate-400">/100</span></span>
                                        <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-600">
                                            {{ ucwords(str_replace('_', ' ', $dealScore->breakdown['label'] ?? '')) }}
                                        </span>
                                    </p>
                                </div>
                                <details class="w-full sm:w-auto">
                                    <summary class="cursor-pointer text-sm font-semibold text-indigo-600 hover:underline">{{ __('How this score is built') }}</summary>
                                    <dl class="mt-3 space-y-1.5 text-sm">
                                        @foreach (($dealScore->breakdown['component_labels'] ?? \App\Services\DealScoreService::COMPONENTS_UI) as $key => $label)
                                            @isset($dealScore->breakdown[$key])
                                                <div class="flex items-center justify-between gap-4">
                                                    <dt class="text-slate-600">{{ $label }}</dt>
                                                    <dd class="font-semibold text-slate-800">{{ $dealScore->breakdown[$key] }}<span class="text-xs text-slate-400">/100</span></dd>
                                                </div>
                                            @endisset
                                        @endforeach
                                    </dl>
                                    <p class="mt-3 text-xs text-slate-400">{{ __('Deal Score is an estimate generated by Distrax, not a guarantee of outcome.') }}</p>
                                </details>
                            </div>
                        @endif

                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <p class="text-xs text-slate-500">{{ __('Asking price') }}</p>
                                <p class="text-lg font-bold text-slate-950">{{ moneyFrom($listing->price, $listing->currency_code) }}</p>
                            </div>
                            @if ($marketValue)
                                <div class="rounded-xl bg-slate-50 px-4 py-3">
                                    <p class="text-xs text-slate-500">{{ __('Estimated market value') }} <span class="text-[10px] uppercase text-indigo-500">Estimate</span></p>
                                    <p class="text-lg font-bold text-slate-950">{{ money($marketValue, $valuation?->currency_code ?? $listing->currency_code) }}</p>
                                </div>
                            @endif
                            @if ($discountPct !== null)
                                <div class="rounded-xl bg-emerald-50 px-4 py-3">
                                    <p class="text-xs text-slate-500">{{ __('Discount vs market') }}</p>
                                    <p class="text-lg font-bold {{ $discountPct >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $discountPct >= 0 ? '−' : '+' }}{{ number_format(abs($discountPct), 1) }}%
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if ($listing->expected_closing_period || $listing->negotiation_flexibility)
                            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                @if ($listing->expected_closing_period)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                                        <i class="h-3.5 w-3.5" data-lucide="clock" aria-hidden="true"></i>
                                        {{ __('Closing') }}: {{ ucwords(str_replace('_', ' ', $listing->expected_closing_period)) }}
                                    </span>
                                @endif
                                @if ($listing->negotiation_flexibility)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                                        <i class="h-3.5 w-3.5" data-lucide="handshake" aria-hidden="true"></i>
                                        {{ __('Negotiation') }}: {{ ucwords(str_replace('_', ' ', $listing->negotiation_flexibility)) }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </section>
                    @endif

                    {{-- ── Deal Analysis (blueprint 3.8) ─────────────────────────── --}}
                    @if ($dealScore || $valuation || $comparables->isNotEmpty())
                    <section id="deal-analysis" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="h-4.5 w-4.5" data-lucide="chart-line" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Deal Analysis') }}</h2>
                            @if ($valuation?->confidence_score !== null)
                                <span class="ml-auto text-xs font-semibold uppercase tracking-wide text-amber-600">
                                    {{ __('Confidence') }}: {{ $valuation->confidence_score }}%
                                </span>
                            @endif
                        </header>

                        @php
                            $pricePerSqm = null;
                            if ($listing->price > 0 && $listing->area_sqft > 0) {
                                $pricePerSqm = round($listing->price / ($listing->area_sqft / 10.7639));
                            }
                            $recentReduction = $listing->priceHistory
                                ->filter(fn ($h) => $h->old_price !== null && $h->new_price < $h->old_price)
                                ->isNotEmpty();
                        @endphp

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Asking price') }}</dt>
                                <dd class="mt-1 font-bold text-slate-950">{{ moneyFrom($listing->price, $listing->currency_code) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Estimated market value') }} <span class="text-[10px] uppercase text-indigo-500">Estimate</span></dt>
                                <dd class="mt-1 font-bold text-slate-950">{{ $marketValue ? money($marketValue, $valuation?->currency_code ?? $listing->currency_code) : __('Not available') }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Price per m²') }} <span class="text-[10px] uppercase text-indigo-500">Estimate</span></dt>
                                <dd class="mt-1 font-bold text-slate-950">{{ $pricePerSqm ? moneyFrom($pricePerSqm, $listing->currency_code) : __('Not available') }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Discount / premium') }}</dt>
                                <dd class="mt-1 font-bold {{ ($discountPct ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $discountPct !== null ? number_format($discountPct, 1).'%' : __('Not available') }}
                                </dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Est. acquisition cost') }} <span class="text-[10px] uppercase text-indigo-500">Estimate</span></dt>
                                <dd class="mt-1 font-bold text-slate-950">{{ moneyFrom($listing->price, $listing->currency_code) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Recent price reduction') }}</dt>
                                <dd class="mt-1 font-bold {{ $recentReduction ? 'text-emerald-600' : 'text-slate-950' }}">{{ $recentReduction ? __('Yes') : ($listing->priceHistory->isNotEmpty() ? __('No') : __('Not available')) }}</dd>
                            </div>
                        </dl>
                        <p class="mt-4 text-xs text-slate-400">{{ __('All model-generated figures are estimates with a confidence level — never presented as fact.') }}</p>
                    </section>
                    @endif

                    {{-- ── Risk Snapshot (blueprint 3.8) ──────────────────────────── --}}
                    @if ($riskAssessments->isNotEmpty())
                    <section id="risk-snapshot" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <i class="h-4.5 w-4.5" data-lucide="shield-alert" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Risk Snapshot') }}</h2>
                        </header>
                        <div class="mt-5 space-y-2">
                            @foreach ($riskAssessments as $risk)
                                <details class="group rounded-xl border border-slate-200 px-4 py-3">
                                    <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold">
                                        <span class="flex items-center gap-2">
                                            <span class="inline-block h-2.5 w-2.5 rounded-full {{ $risk->level === 'high' ? 'bg-rose-500' : ($risk->level === 'medium' ? 'bg-amber-400' : 'bg-emerald-500') }}"></span>
                                            {{ ucwords(str_replace('_', ' ', $risk->risk_area)) }}
                                        </span>
                                        <span class="uppercase {{ $risk->level === 'high' ? 'text-rose-600' : ($risk->level === 'medium' ? 'text-amber-600' : 'text-emerald-600') }}">{{ $risk->level }}</span>
                                    </summary>
                                    <p class="mt-2 text-sm text-slate-600">{{ $risk->why_explanation }}</p>
                                    @if ($risk->evidence_ref_id)
                                        <a href="#document-vault" class="mt-1 inline-block text-xs font-semibold text-indigo-600 hover:underline">Evidence: {{ $risk->evidence_ref_id }}</a>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    {{-- ── Investment Potential (blueprint 3.9) ───────────────────── --}}
                    @if (auth()->check() && $listing->price > 0)
                    <section id="investment-potential" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                                <i class="h-4.5 w-4.5" data-lucide="trending-up" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Investment Potential') }}</h2>
                        </header>
                        <p class="mt-4 text-sm text-slate-500">{{ __('Investment figures are estimates to support your own underwriting — not investment advice. Use the tool above to adjust your own assumptions.') }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ __('Use a calculator to model yield, flip margin, or development returns. These change per your own inputs, which are saved to your account.') }}</p>
                    </section>
                    @endif

                    {{-- ── Price History & Timeline (blueprint 3.10) ──────────────── --}}
                    @if ($priceHistory->isNotEmpty() || $timelineEvents->isNotEmpty())
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <i class="h-4.5 w-4.5" data-lucide="history" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Price History & Timeline') }}</h2>
                        </header>

                        @if ($priceHistory->isNotEmpty())
                            <h3 class="mt-5 text-sm font-bold text-slate-800">{{ __('Price history') }}</h3>
                            <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                                @foreach ($priceHistory->reverse() as $entry)
                                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                        <span>{{ $entry->changed_at->format('M Y') }}</span>
                                        <span><span class="line-through text-slate-400">{{ moneyFrom($entry->old_price, $entry->currency_code ?? $listing->currency_code) }}</span> → <span class="font-semibold text-slate-800">{{ moneyFrom($entry->new_price, $entry->currency_code ?? $listing->currency_code) }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($timelineEvents->isNotEmpty())
                            <h3 class="mt-5 text-sm font-bold text-slate-800">{{ __('Property timeline') }}</h3>
                            <ol class="mt-3 space-y-3 border-slate-200">
                                @foreach ($timelineEvents->filter(fn ($e) => $e->privacy_level !== 'internal')->reverse() as $event)
                                    <li class="relative flex gap-3 text-sm">
                                        <span class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full bg-indigo-400"></span>
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ ucwords(str_replace('_', ' ', $event->event_type)) }}</p>
                                            @if ($event->description)<p class="text-slate-600">{{ $event->description }}</p>@endif
                                            <p class="text-xs text-slate-400">{{ $event->occurred_at->format('M j, Y') }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                    @endif

                    {{-- ── Ask Distrax AI (blueprint 3.17) ─────────────────────── --}}
                    <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/60 to-violet-50/40 p-6 shadow-sm dark:border-night-700 dark:from-night-900 dark:to-night-900 sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-night-800">
                                <i class="h-4.5 w-4.5" data-lucide="sparkles" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Ask Distrax AI') }}</h2>
                        </header>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Ask about this specific property. Distrax answers only from this property\'s verified data — it will say "not available" rather than guess.') }}
                        </p>

                        @if (session('ask_distrax'))
                            <div class="mt-4 rounded-xl border border-indigo-200 bg-white p-4 text-sm leading-6 text-slate-700 dark:border-night-700 dark:bg-night-800 dark:text-slate-200">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-600 dark:bg-night-700">
                                        {{ __(str_replace('_', ' ', session('ask_distrax.answer_type'))) }}
                                    </span>
                                </div>
                                <div class="space-y-1 whitespace-pre-line">{!! e(session('ask_distrax.answer')) !!}</div>
                                <button type="button" @click="this.closest('div').remove()"
                                    class="mt-2 text-xs font-medium text-slate-400 hover:text-slate-600">{{ __('Dismiss') }}</button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('properties.ask-distrax', $listing) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <label class="sr-only" for="ask-distrax-question">{{ __('Your question') }}</label>
                            <input id="ask-distrax-question" type="text" name="question" maxlength="1000" required
                                placeholder="{{ __('e.g. Why is this a deal? What are the disclosed risks?') }}"
                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-200">
                            <button type="submit" class="shrink-0 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Ask</button>
                        </form>
                        <p class="mt-2 text-[11px] text-slate-400">{{ __('Every query is logged for quality review. Answers are informational, not professional advice.') }}</p>
                    </section>

                    {{-- ── Comparable Properties (blueprint 3.10) ─────────────────── --}}
                    @if ($comparables->isNotEmpty())
                    <section id="comparables" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                <i class="h-4.5 w-4.5" data-lucide="scale" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Comparable Properties') }}</h2>
                        </header>
                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($comparables->take(6) as $comparable)
                                <a href="{{ $comparable->listing ? route('properties.show', $comparable->listing) : '#' }}"
                                    class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:bg-slate-50">
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ moneyFrom($comparable->sale_price, $listing->currency_code) }}</p>
                                        @if ($comparable->distance_km !== null)
                                            <p class="text-xs text-slate-500">{{ number_format($comparable->distance_km, 1) }} km away</p>
                                        @endif
                                    </div>
                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-600">{{ $comparable->similarity_score }}% match</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    {{-- ── Decision Summary (blueprint 3.6, clearly labelled) ─────── --}}
                    @if ($dealScore || $riskAssessments->isNotEmpty())
                    <section aria-label="Decision summary" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <i class="h-4.5 w-4.5" data-lucide="scroll-text" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Decision Summary') }}</h2>
                        </header>
                        <p class="mt-3 text-sm text-slate-600">
                            {{ __('This summary is generated by Distrax from the property\u2019s stored records. It is not financial or legal advice.') }}
                        </p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-700">
                            @if ($dealScore)
                                <li>- {{ __('Deal Score of :score/100 placed this property :label.', ['score' => $dealScore->score, 'label' => ucwords(str_replace('_', ' ', $dealScore->breakdown['label'] ?? ''))]) }}</li>
                            @endif
                            @if ($discountPct !== null)
                                <li>- {{ __('Priced :pct off the estimated market value.', ['pct' => number_format(abs($discountPct), 1).'%']) }}</li>
                            @endif
                            @if ($highRisks = $riskAssessments->where('level', 'high')->count())
                                <li>- {{ trans_choice(':count high-risk area(s) identified — review the Risk Snapshot.', $highRisks, ['count' => $highRisks]) }}</li>
                            @endif
                        </ul>
                    </section>
                    @endif

                    {{-- Description --}}
                    <section id="description" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="h-4.5 w-4.5" data-lucide="align-left" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Description') }}</h2>
                            @if (setting('google_translate_api_key') && $listing->language_tag && substr($listing->language_tag, 0, 2) !== substr(app()->getLocale(), 0, 2))
                                <button type="button" data-translate-url="{{ route('api.v1.listings.translate', $listing) }}"
                                    data-target-lang="{{ substr(app()->getLocale(), 0, 2) }}"
                                    x-data="{ loading: false }"
                                    @click="
                                        loading = true;
                                        fetch($el.dataset.translateUrl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                                            body: JSON.stringify({ target: $el.dataset.targetLang }),
                                        })
                                            .then((r) => r.json())
                                            .then((res) => {
                                                if (res.data?.description) {
                                                    document.getElementById('listing-description-text').innerText = res.data.description;
                                                }
                                                loading = false;
                                            })
                                            .catch(() => { loading = false; });
                                    "
                                    :disabled="loading"
                                    class="ms-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50 disabled:opacity-60">
                                    <i class="h-3.5 w-3.5" data-lucide="languages" aria-hidden="true"></i>
                                    <span x-text="loading ? '{{ __('Translating…') }}' : '{{ __('Translate') }}'"></span>
                                </button>
                            @endif
                        </header>
                        @if (filled($listing->description))
                            @php
                                // Rich-text descriptions are sanitized on save (SanitizedHtml cast) so they are
                                // safe to render as HTML. Legacy plain-text descriptions have no tags — keep their
                                // line breaks by escaping and converting newlines instead of collapsing them.
                                $descriptionHtml = strip_tags($listing->description) === $listing->description
                                    ? nl2br(e($listing->description))
                                    : $listing->description;
                            @endphp
                            <div id="listing-description-text" class="mt-5 break-words text-[15px] leading-7 text-slate-600
                                [&>*:first-child]:mt-0 [&>*:first-child]:border-0 [&>*:first-child]:pt-0
                                [&>h1]:mt-8 [&>h1]:mb-4 [&>h1]:text-2xl [&>h1]:font-bold [&>h1]:leading-snug [&>h1]:tracking-tight [&>h1]:text-slate-950
                                [&>h2]:mt-8 [&>h2]:mb-3 [&>h2]:border-t [&>h2]:border-slate-100 [&>h2]:pt-6 [&>h2]:text-xl [&>h2]:font-bold [&>h2]:text-slate-950
                                [&>h3]:mt-7 [&>h3]:mb-2.5 [&>h3]:border-t [&>h3]:border-slate-100 [&>h3]:pt-5 [&>h3]:text-lg [&>h3]:font-bold [&>h3]:text-slate-900
                                [&>h4]:mt-6 [&>h4]:mb-2 [&>h4]:text-sm [&>h4]:font-bold [&>h4]:uppercase [&>h4]:tracking-wide [&>h4]:text-slate-500
                                [&>p]:mb-4
                                [&_a]:font-medium [&_a]:text-indigo-600 [&_a]:underline [&_strong]:font-semibold [&_strong]:text-slate-900
                                [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:ps-6 [&_ul]:space-y-1.5 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_ol]:space-y-1.5
                                [&_li]:leading-7 [&_li]:marker:text-indigo-400
                                [&_blockquote]:my-4 [&_blockquote]:border-s-4 [&_blockquote]:border-indigo-500 [&_blockquote]:ps-4 [&_blockquote]:italic [&_blockquote]:text-slate-600
                                [&_hr]:my-6 [&_hr]:border-slate-100
                                [&_img]:my-4 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-xl
                                [&_table]:my-4 [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto">
                                {!! $descriptionHtml !!}
                            </div>
                        @else
                            <p class="mt-4 text-[15px] leading-7 text-slate-600">{{ __('The owner has not added a description yet.') }}</p>
                        @endif
                    </section>

                    {{-- Property details --}}
                    @php
                        // Configuration = the unit's physical specs. Each entry is a label→value
                        // pair rendered as a compact stacked cell so the eye never travels far.
                        $configSpecs = collect([
                            ['label' => __('Bedrooms'), 'value' => $listing->bedrooms],
                            ['label' => __('Bathrooms'), 'value' => $listing->bathrooms],
                            ['label' => __('Kitchen'), 'value' => $customValues['kitchen']['value'] ?? null],
                            ['label' => __('Balcony'), 'value' => $customValues['balcony']['value'] ?? null],
                            ['label' => __('Floor'), 'value' => $listing->floor ? $ordinal($listing->floor) : null],
                            ['label' => __('Total Floors'), 'value' => $listing->total_floors],
                            ['label' => __('Area'), 'value' => $listing->area_sqft ? __(':area Sq Ft', ['area' => number_format($listing->area_sqft)]) : null],
                            ['label' => __('Allowed For'), 'value' => filled($listing->allowed_for) ? __(ucfirst($listing->allowed_for)) : null],
                        ])->filter(fn (array $row): bool => filled($row['value']))->values();

                        // Yes/no facts read faster as coloured pills than as "Yes"/"Not available" text.
                        $factPills = collect([
                            ['on' => (bool) $listing->furnished, 'on_text' => __('Furnished'), 'off_text' => __('Unfurnished')],
                            ['on' => (bool) $listing->parking, 'on_text' => __('Parking'), 'off_text' => __('No parking')],
                        ]);

                        $listingInfo = collect([
                            ['label' => __('Purpose'), 'value' => $purposeLabels[$listing->type] ?? __(ucfirst($listing->type))],
                            ['label' => __('Service Charge'), 'value' => $listing->service_charge ? moneyFrom($listing->service_charge, $listing->currency_code) : null],
                            ['label' => __('Listed On'), 'value' => $listing->published_at?->format('M j, Y')],
                        ])->filter(fn (array $row): bool => filled($row['value']))->values();
                    @endphp
                    <section id="details" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <header class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="h-4.5 w-4.5" data-lucide="clipboard-list" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold text-slate-950">{{ __('Property details') }}</h2>
                        </header>

                        <h3 class="mt-6 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Configuration') }}</h3>
                        <dl class="mt-1 grid grid-cols-2 gap-x-8 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($configSpecs as $spec)
                                <div class="border-b border-slate-100 py-3">
                                    <dt class="text-xs text-slate-500">{{ $spec['label'] }}</dt>
                                    <dd class="mt-0.5 text-[15px] font-bold text-slate-900">{{ $spec['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($factPills as $pill)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $pill['on'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <i class="h-3.5 w-3.5 shrink-0" data-lucide="{{ $pill['on'] ? 'check' : 'x' }}" aria-hidden="true"></i>
                                    {{ $pill['on'] ? $pill['on_text'] : $pill['off_text'] }}
                                </span>
                            @endforeach
                        </div>

                        <h3 class="mt-7 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Listing info') }}</h3>
                        <dl class="mt-1 grid grid-cols-2 gap-x-8 sm:grid-cols-3 lg:grid-cols-4">
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-600">{{ __(ucfirst($listing->status)) }}</span>
                                </dd>
                            </div>
                            @foreach ($listingInfo as $row)
                                <div class="border-b border-slate-100 py-3">
                                    <dt class="text-xs text-slate-500">{{ $row['label'] }}</dt>
                                    <dd class="mt-0.5 text-[15px] font-bold text-slate-900">{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <p class="mt-4 text-xs text-slate-400">{{ __('Ref') }}&nbsp;#{{ $listing->id }}</p>
                    </section>

                    {{-- Amenities --}}
                    @if ($amenities->isNotEmpty())
                        <section id="amenities" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="sparkles" aria-hidden="true"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-950">{{ __('Amenities') }}</h2>
                            </header>
                            <ul class="mt-5 grid grid-cols-1 gap-3 text-[15px] text-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($amenities as $amenity)
                                    <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm">
                                            <i class="h-4 w-4" data-lucide="{{ $amenity['icon'] }}" aria-hidden="true"></i>
                                        </span>
                                        {{ $amenity['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Floor plan --}}
                    @if ($floorPlans->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="scan" aria-hidden="true"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-950">{{ __('Floor Plan') }}</h2>
                            </header>
                            <div class="mt-5 space-y-4">
                                @foreach ($floorPlans as $floorPlan)
                                    <figure class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                        <img class="max-h-96 w-full bg-white object-contain p-3" src="{{ $mediaUrl($floorPlan) }}"
                                            alt="{{ __('Floor plan for :title', ['title' => $listing->title]) }}" loading="lazy">
                                        <figcaption class="border-t border-slate-200 p-3 text-center">
                                            <a href="{{ $mediaUrl($floorPlan) }}" target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700">
                                                <i class="h-4 w-4" data-lucide="maximize" aria-hidden="true"></i>{{ __('View Full Size') }}
                                            </a>
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Location --}}
                    @if ($listing->lat && $listing->lng)
                        <section id="location" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7"
                            x-data="{ loading: false, result: null }">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="map-pin" aria-hidden="true"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-950">{{ __('Location') }}</h2>
                            </header>
                            <p class="mt-3 flex items-center gap-1.5 text-sm text-slate-600">
                                <i class="h-4 w-4 text-slate-500" data-lucide="map-pin" aria-hidden="true"></i>
                                {{ $listing->address ?? $listing->zone?->name }}
                            </p>

                            @if ($lowDataMode ?? false)
                                <p class="mt-4 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">{{ __('The map is hidden in low-data mode.') }}</p>
                                <a class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                    href="https://www.openstreetmap.org/?mlat={{ $listing->lat }}&mlon={{ $listing->lng }}#map=16/{{ $listing->lat }}/{{ $listing->lng }}"
                                    target="_blank" rel="noopener">
                                    <i class="h-4 w-4" data-lucide="map" aria-hidden="true"></i>{{ __('Open map in a new tab') }}
                                </a>
                            @else
                                <div id="listing-map" class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                                    <iframe class="h-72 w-full border-0" src="{{ $listingMapSrc }}"
                                        title="{{ __('Map showing the location of :title', ['title' => $listing->title]) }}"
                                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                        allowfullscreen></iframe>
                                </div>
                            @endif

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $listing->lat }},{{ $listing->lng }}"
                                    target="_blank" rel="noopener"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                    <i class="h-4 w-4" data-lucide="external-link" aria-hidden="true"></i>{{ __('View on Google Maps') }}
                                </a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $listing->lat }},{{ $listing->lng }}"
                                    target="_blank" rel="noopener"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">
                                    <i class="h-4 w-4" data-lucide="navigation" aria-hidden="true"></i>{{ __('Get Directions') }}
                                </a>
                            </div>

                            <button type="button" data-distance-url="{{ route('properties.distance', $listing) }}"
                                @click="
                                    loading = true; result = 'Locating you…';
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            const url = new URL($el.dataset.distanceUrl);
                                            url.searchParams.set('lat', position.coords.latitude);
                                            url.searchParams.set('lng', position.coords.longitude);
                                            fetch(url, { headers: { Accept: 'application/json' } })
                                                .then((response) => response.json())
                                                .then((data) => { result = data.distance + ' away · ~' + data.drive_minutes + ' min drive · ~' + data.walk_minutes + ' min walk'; loading = false; })
                                                .catch(() => { result = 'Could not calculate distance.'; loading = false; });
                                        },
                                        () => { result = 'Location permission denied.'; loading = false; },
                                        { timeout: 8000 },
                                    );
                                "
                                class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-60"
                                :disabled="loading">
                                <i class="h-4 w-4" data-lucide="ruler" aria-hidden="true"></i>{{ __('Distance from my location') }}
                            </button>

                            @if (setting('office_lat') && setting('office_lng'))
                                <button type="button" data-distance-url="{{ route('properties.distance', $listing) }}"
                                    data-office-lat="{{ setting('office_lat') }}" data-office-lng="{{ setting('office_lng') }}"
                                    @click="
                                        loading = true; result = 'Calculating…';
                                        const url = new URL($el.dataset.distanceUrl);
                                        url.searchParams.set('lat', $el.dataset.officeLat);
                                        url.searchParams.set('lng', $el.dataset.officeLng);
                                        fetch(url, { headers: { Accept: 'application/json' } })
                                            .then((response) => response.json())
                                            .then((data) => { result = data.distance + ' from our office · ~' + data.drive_minutes + ' min drive · ~' + data.walk_minutes + ' min walk'; loading = false; })
                                            .catch(() => { result = 'Could not calculate distance.'; loading = false; });
                                    "
                                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-60"
                                    :disabled="loading">
                                    <i class="h-4 w-4" data-lucide="building-2" aria-hidden="true"></i>{{ __('Distance from our office') }}
                                </button>
                            @endif
                            <p x-show="result" x-cloak class="mt-3 text-sm text-slate-600" x-text="result"></p>
                        </section>
                    @endif

                    {{-- Nearby amenities --}}
                    @if (! empty($nearby))
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="map-pin" aria-hidden="true"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-950">{{ __('Nearby') }}</h2>
                            </header>
                            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach ($nearby as $place)
                                    <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2 text-sm">
                                        <span class="truncate">
                                            <span class="font-semibold text-slate-800">{{ $place['name'] }}</span>
                                            <span class="text-slate-400">· {{ $place['category'] }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-500">{{ $place['distance_meters'] }} m</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Video tour --}}
                    @if ($listing->videos->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="circle-play" aria-hidden="true"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-950">{{ __('Video tour') }}</h2>
                            </header>
                            <a class="mt-4 inline-flex items-center gap-2 font-semibold text-indigo-600 transition hover:text-indigo-700"
                                href="{{ $listing->videos->first()->resolvedUrl() }}" target="_blank" rel="noopener">
                                <i class="h-5 w-5" data-lucide="circle-play" aria-hidden="true"></i>{{ __('Watch video tour') }}
                            </a>
                        </section>
                    @endif

                    {{-- Reviews --}}
                    <section id="reviews" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <header class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <i class="h-4.5 w-4.5" data-lucide="star" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-950">{{ __('Reviews (:count)', ['count' => $listing->reviews_count]) }}</h2>
                                    @if ($listing->reviews_count > 0)
                                        <p class="mt-0.5 flex items-center gap-1.5 text-sm text-slate-600">
                                            <i class="h-4 w-4 fill-current text-amber-400" data-lucide="star" aria-hidden="true"></i>
                                            {{ __(':rating average rating', ['rating' => number_format((float) ($listing->reviews_avg_rating ?? 0), 1)]) }}
                                        </p>
                                    @endif
                                </div>
                            </header>
                            @auth
                                <a href="#write-review" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                    <i class="h-4 w-4" data-lucide="pencil" aria-hidden="true"></i>{{ __('Write a Review') }}
                                </a>
                            @endauth
                        </div>

                        @if ($listing->reviews->isEmpty())
                            <div class="mt-6 flex flex-col items-center gap-2 rounded-2xl bg-slate-50 px-6 py-10 text-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-amber-400 shadow-sm">
                                    <i class="h-6 w-6" data-lucide="message-square-heart" aria-hidden="true"></i>
                                </span>
                                <p class="text-sm font-bold text-slate-800">{{ __('No reviews yet.') }}</p>
                                <p class="text-sm text-slate-500">{{ __('Be the first to share your experience.') }}</p>
                            </div>
                        @else
                            @php $avgRating = (float) ($listing->reviews_avg_rating ?? 0); @endphp
                            {{-- Rating summary --}}
                            <div class="mt-6 flex items-center gap-5 rounded-2xl border border-slate-100 bg-slate-50 p-5">
                                <div class="shrink-0 text-center">
                                    <p class="text-4xl font-extrabold leading-none text-slate-950">{{ number_format($avgRating, 1) }}</p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ __('out of 5') }}</p>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex gap-0.5" aria-hidden="true">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="h-5 w-5 {{ $i <= round($avgRating) ? 'fill-current text-amber-400' : 'text-slate-300' }}" data-lucide="star"></i>
                                        @endfor
                                    </div>
                                    <p class="mt-1.5 text-sm text-slate-600">
                                        {{ __('Based on') }} <span class="font-semibold text-slate-900">{{ $listing->reviews_count }}</span> {{ \Illuminate\Support\Str::plural('review', $listing->reviews_count) }}
                                    </p>
                                </div>
                            </div>

                            {{-- Review list --}}
                            <div class="mt-5 space-y-4">
                                @foreach ($listing->reviews as $review)
                                    @php
                                        $avatar = $review->reviewer?->avatar;
                                        $avatarUrl = $avatar
                                            ? (\Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://']) ? $avatar : asset('storage/'.$avatar))
                                            : null;
                                    @endphp
                                    <article class="flex gap-4 rounded-2xl border border-slate-100 p-4 sm:p-5">
                                        @if ($avatarUrl)
                                            <img class="h-11 w-11 shrink-0 rounded-full object-cover" src="{{ $avatarUrl }}" alt="" width="44" height="44" loading="lazy">
                                        @else
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600" aria-hidden="true">
                                                {{ \Illuminate\Support\Str::of($review->reviewer?->name ?? 'User')->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') }}
                                            </span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                                <p class="font-bold text-slate-900">{{ $review->reviewer?->name ?? __('Verified user') }}</p>
                                                <time class="text-xs text-slate-500">{{ $review->created_at->format('M j, Y') }}</time>
                                            </div>
                                            <div class="mt-1 flex gap-0.5" aria-label="{{ $review->rating }} out of 5 stars">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="h-4 w-4 {{ $i <= $review->rating ? 'fill-current text-amber-400' : 'text-slate-300' }}" data-lucide="star" aria-hidden="true"></i>
                                                @endfor
                                            </div>
                                            <p class="mt-2 text-[15px] leading-6 text-slate-600">{{ $review->body }}</p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif

                        @auth
                            <form id="write-review" x-data="{ rating: 0, hover: 0 }" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                                action="{{ route('reviews.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="reviewable_type" value="listing">
                                <input type="hidden" name="reviewable_id" value="{{ $listing->id }}">
                                <input type="hidden" name="rating" :value="rating">

                                <p class="text-sm font-bold text-slate-900">{{ __('Write a review') }}</p>

                                <div class="mt-3 flex items-center gap-1" @mouseleave="hover = 0" role="radiogroup" aria-label="{{ __('Your rating') }}">
                                    <template x-for="star in 5" :key="star">
                                        <button type="button" @click="rating = star" @mouseenter="hover = star"
                                            :aria-label="star + ' star' + (star > 1 ? 's' : '')" :aria-pressed="rating === star"
                                            class="transition hover:scale-110"
                                            :class="(hover || rating) >= star ? 'text-amber-400' : 'text-slate-300'">
                                            <svg viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7">
                                                <path d="M12 2l2.9 6.26 6.9.54-5.25 4.52 1.62 6.68L12 17.27 5.83 20l1.62-6.68L2.2 8.8l6.9-.54L12 2z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <span x-show="rating" x-cloak class="ms-2 text-sm font-semibold text-slate-600" x-text="rating + ' / 5'"></span>
                                </div>

                                <label class="sr-only" for="review-body">{{ __('Your review') }}</label>
                                <textarea id="review-body" class="mt-3 w-full resize-y rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/10"
                                    name="body" rows="3" maxlength="2000" placeholder="{{ __('Share your experience with this property…') }}"></textarea>

                                <button type="submit" :disabled="rating === 0"
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>{{ __('Submit review') }}
                                </button>
                            </form>
                        @endauth
                    </section>

                    {{-- Similar properties --}}
                    @if ($similarListings->isNotEmpty())
                        <section>
                            <div class="mb-5 flex items-center justify-between">
                                <h2 class="text-xl font-bold tracking-tight text-slate-950">{{ __('Similar properties') }}</h2>
                                <a class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                    href="{{ route('properties.index', ['type' => $listing->type, 'zone_id' => $listing->zone_id]) }}">{{ __('View all') }}</a>
                            </div>
                            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($similarListings as $similar)
                                    <x-website.property-card :listing="$similar" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
                <aside class="space-y-5 lg:sticky lg:top-28">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-2xl font-extrabold text-indigo-600 sm:text-3xl">
                            {{ moneyFrom($listing->price, $listing->currency_code) }}@if ($priceSuffix)<span class="text-sm font-medium text-slate-500">{{ $priceSuffix }}</span>@endif
                        </p>
                        @if ($listing->is_negotiable)
                            <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-indigo-600">
                                <i class="h-4 w-4" data-lucide="circle-help" aria-hidden="true"></i>{{ __('Price is Negotiable') }}
                            </p>
                        @endif

                        <p class="mt-4 text-xl font-bold leading-snug text-slate-950">{{ $listing->title }}</p>
                        <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600">
                            <i class="h-4 w-4 shrink-0 text-indigo-500" data-lucide="map-pin" aria-hidden="true"></i>
                            {{ $listing->address ?? $listing->zone?->name }}
                        </p>

                        @if ($stats->isNotEmpty())
                            <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-slate-100 pt-5">
                                @foreach ($stats as $stat)
                                    <div class="flex flex-col items-center gap-1.5 rounded-xl bg-slate-50 px-2 py-3 text-center">
                                        <i class="h-5 w-5 shrink-0 text-indigo-500" data-lucide="{{ $stat['icon'] }}" aria-hidden="true"></i>
                                        <dd class="text-sm font-bold text-slate-950">{{ $stat['value'] }}</dd>
                                        <dt class="text-xs text-slate-500">{{ $stat['label'] }}</dt>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <div class="mt-6 space-y-3">
                            <a href="#contact"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                <i class="h-4 w-4" data-lucide="mail" aria-hidden="true"></i>{{ __('Request Information') }}
                            </a>
                            <a href="{{ auth()->check() ? '#schedule-visit' : '#contact' }}"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 px-4 py-3 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                <i class="h-4 w-4" data-lucide="calendar" aria-hidden="true"></i>{{ __('Schedule a Visit') }}
                            </a>
                            @if (auth()->check() && $listing->owner_id && $listing->owner_id !== auth()->id() && $listing->status === 'active')
                                <a href="{{ route('offers.create', $listing) }}"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <i class="h-4 w-4" data-lucide="hand-coins" aria-hidden="true"></i>{{ __('Make an Offer') }}
                                </a>
                            @endif
                            @if (auth()->check() && $listing->owner_id && $listing->owner_id !== auth()->id())
                                <a href="{{ route('inspections.create', $listing) }}"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-200 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                    <i class="h-4 w-4" data-lucide="scan-search" aria-hidden="true"></i>{{ __('Book an Inspection') }}
                                </a>
                            @endif
                            <a href="#share"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">
                                <i class="h-4 w-4" data-lucide="share-2" aria-hidden="true"></i>{{ __('Share Property') }}
                            </a>
                        </div>
                    </section>

                    {{-- Agent / owner --}}
                    <section id="contact" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-sm font-bold text-slate-950">{{ __('Agent / Owner') }}</h2>
                            @auth
                                <button type="button" @click="reportOpen = !reportOpen"
                                    class="rounded-lg p-1 text-slate-500 transition hover:text-slate-700" aria-label="{{ __('More options') }}">
                                    <i class="h-5 w-5" data-lucide="ellipsis-vertical" aria-hidden="true"></i>
                                </button>
                            @endauth
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            @if ($listing->agency?->logo)
                                <img class="h-10 w-10 shrink-0 rounded-full bg-white object-contain ring-1 ring-slate-100"
                                    src="{{ asset('storage/'.$listing->agency->logo) }}" alt="" width="40" height="40">
                            @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600" aria-hidden="true">
                                    {{ str($listing->agency?->name ?? $listing->owner?->name ?? 'O')->substr(0, 1)->upper() }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="flex items-center gap-1.5 font-bold text-slate-900">
                                    <span class="truncate">{{ $listing->agency?->name ?? $listing->owner?->name ?? __('Property owner') }}</span>
                                    @if ($listing->owner?->verification_status === 'verified' || $listing->agency?->is_verified)
                                        <i class="h-4 w-4 shrink-0 text-emerald-500" data-lucide="badge-check" aria-label="{{ __('Verified') }}"></i>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500">{{ $listing->agency ? __('Agency listing') : __('Direct owner') }}</p>
                            </div>
                        </div>

                        @if ($sellerReputation && ! $listing->agency)
                            <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center dark:bg-night-800">
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $sellerReputation['rating'] > 0 ? number_format($sellerReputation['rating'], 1).' / 5' : __('—') }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('Rating') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $sellerReputation['completed_deals_count'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('Deals closed') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $sellerReputation['response_time_avg_minutes'] !== null ? __('~:min min', ['min' => $sellerReputation['response_time_avg_minutes']]) : __('—') }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">{{ __('Avg reply') }}</p>
                                </div>
                            </div>
                            @if ($sellerReputation['offer_response_rate'] !== null)
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Responds to :pct% of offers', ['pct' => round($sellerReputation['offer_response_rate'] * 100)]) }}
                                    · {{ __(':count disclosures', ['count' => $sellerReputation['disclosure_count']]) }}
                                </p>
                            @endif
                        @endif

                        @auth
                            <div x-data="{ contactTab: '{{ $listing->type === 'hotel' ? 'book' : 'message' }}' }" class="mt-5">
                                <div class="flex rounded-lg bg-slate-100 p-1 text-sm font-semibold">
                                    @if ($listing->type === 'hotel')
                                        <button type="button" @click="contactTab = 'book'"
                                            :class="contactTab === 'book' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'"
                                            class="flex-1 rounded-md px-3 py-1.5 transition">{{ __('Book') }}</button>
                                    @endif
                                    <button type="button" @click="contactTab = 'message'"
                                        :class="contactTab === 'message' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'"
                                        class="flex-1 rounded-md px-3 py-1.5 transition">{{ __('Message') }}</button>
                                    <button type="button" @click="contactTab = 'call'"
                                        :class="contactTab === 'call' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'"
                                        class="flex-1 rounded-md px-3 py-1.5 transition">{{ __('Call') }}</button>
                                </div>

                                @if ($listing->type === 'hotel')
                                    @php
                                        $cancellationPolicy = setting('cancellation_policy', 'flexible');
                                        $cancellationPolicyText = [
                                            'flexible' => __('Free cancellation up to 24 hours before check-in.'),
                                            'moderate' => __('Free cancellation up to 5 days before check-in.'),
                                            'strict' => __('50% refund if cancelled more than 7 days before check-in; no refund after.'),
                                            'non_refundable' => __('This booking is non-refundable.'),
                                        ][$cancellationPolicy] ?? __('No refund policy configured.');
                                    @endphp
                                    <form x-show="contactTab === 'book'" x-cloak
                                        x-data="{
                                            checking: false, available: null, nights: 0, total: 0,
                                            checkAvailability(checkIn, checkOut) {
                                                if (!checkIn || !checkOut) return;
                                                this.checking = true; this.available = null;
                                                fetch(`{{ route('api.v1.listings.availability', $listing) }}?from=${checkIn}&to=${checkOut}`, { headers: { Accept: 'application/json' } })
                                                    .then((r) => r.json())
                                                    .then((res) => {
                                                        const dates = res.data?.dates ?? [];
                                                        this.available = dates.every((d) => d.is_available);
                                                        this.nights = dates.length;
                                                        this.total = dates.reduce((sum, d) => sum + (d.price ?? {{ (int) $listing->price }}), 0);
                                                        this.checking = false;
                                                    })
                                                    .catch(() => { this.checking = false; });
                                            },
                                        }"
                                        class="mt-4 space-y-2 rounded-xl bg-indigo-50 p-3"
                                        action="{{ route('hotel-bookings.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                                        <p class="text-sm font-semibold text-indigo-950">{{ __('Book this stay') }}</p>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <label class="text-xs">{{ __('Check in') }}<input id="booking-check-in" class="mt-1 w-full rounded-lg border border-slate-200 p-2" type="date" name="check_in" min="{{ today()->toDateString() }}" required
                                                @change="checkAvailability($event.target.value, document.getElementById('booking-check-out').value)"
                                            ></label>
                                            <label class="text-xs">{{ __('Check out') }}<input id="booking-check-out" class="mt-1 w-full rounded-lg border border-slate-200 p-2" type="date" name="check_out" min="{{ today()->addDay()->toDateString() }}" required
                                                @change="checkAvailability(document.getElementById('booking-check-in').value, $event.target.value)"
                                            ></label>
                                        </div>

                                        <div x-show="checking" x-cloak class="text-xs text-slate-500">{{ __('Checking availability…') }}</div>
                                        <div x-show="available === true" x-cloak class="rounded-lg bg-emerald-50 p-2 text-xs font-semibold text-emerald-700">
                                            <span x-text="nights"></span> {{ __('night(s) available') }} &middot; {{ __('estimated total') }} <span x-text="total"></span> {{ setting('default_currency', 'BDT') }}
                                        </div>
                                        <div x-show="available === false" x-cloak class="rounded-lg bg-red-50 p-2 text-xs font-semibold text-red-700">
                                            {{ __('Some dates in this range are already booked. Please choose different dates.') }}
                                        </div>

                                        <label class="text-xs" for="booking-guests">{{ __('Guests') }}<input id="booking-guests" class="mt-1 w-full rounded-lg border border-slate-200 p-2 text-sm" type="number" name="guests" min="1" max="20" value="1" placeholder="{{ __('Guests') }}"></label>
                                        <label class="sr-only" for="booking-requests">{{ __('Special requests') }}</label>
                                        <textarea id="booking-requests" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="special_requests" placeholder="{{ __('Special requests') }}"></textarea>
                                        <label class="sr-only" for="booking-payment-method">{{ __('Payment method') }}</label>
                                        <select id="booking-payment-method" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="payment_method">
                                            <option value="wallet">{{ __('Pay with wallet') }}</option>
                                            @foreach ($activeGateways as $activeGateway)
                                                <option value="{{ $activeGateway }}">{{ __('Pay with :gateway', ['gateway' => str($activeGateway)->title()]) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="w-full rounded-lg bg-indigo-600 p-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700" :disabled="available === false">{{ __('Request booking') }}</button>
                                        <p class="text-xs text-slate-500"><i class="me-1 inline h-3 w-3" data-lucide="shield-check" aria-hidden="true"></i>{{ __('Cancellation policy:') }} {{ $cancellationPolicyText }}</p>
                                    </form>
                                @endif

                                <form x-show="contactTab === 'message'" x-cloak class="mt-4" action="{{ route('conversations.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                                    <p class="text-sm leading-6 text-slate-600">{{ __('Hi, I am interested in this property. Can you please provide more details?') }}</p>
                                    <label class="sr-only" for="contact-message">{{ __('Your message') }}</label>
                                    <textarea id="contact-message" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm placeholder:text-slate-400"
                                        name="message" rows="3" maxlength="2000" required
                                        placeholder="{{ __('Write your message...') }}">{{ __('Hi, I am interested in :title. Is it still available?', ['title' => $listing->title]) }}</textarea>
                                    <button class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700" type="submit">
                                        <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>{{ __('Send Message') }}
                                    </button>
                                </form>

                                <div x-show="contactTab === 'call'" x-cloak class="mt-4">
                                    @if (session('revealedPhone'))
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                            <span class="font-semibold">{{ session('revealedPhone') }}</span>
                                            <span class="mt-1 block text-xs text-emerald-700">{{ __(':count reveals left this month.', ['count' => session('revealRemaining')]) }}</span>
                                        </div>
                                    @else
                                        <form action="{{ route('properties.reveal-contact', $listing) }}" method="POST">
                                            @csrf
                                            <button class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600" type="submit">
                                                <i class="h-4 w-4" data-lucide="phone" aria-hidden="true"></i>{{ __('Reveal phone number') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div id="schedule-visit" class="mt-5 scroll-mt-28 space-y-2 border-t border-slate-100 pt-5">
                                <form action="{{ route('properties.visits.store', $listing) }}" method="POST">
                                    @csrf
                                    <label class="block text-xs font-semibold text-slate-600" for="visit-time">{{ __('Preferred visit time') }}</label>
                                    <input id="visit-time" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" type="datetime-local"
                                        name="scheduled_at" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required>
                                    <label class="sr-only" for="visit-note">{{ __('Note') }}</label>
                                    <input id="visit-note" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="note" maxlength="500" placeholder="{{ __('Optional note') }}">
                                    <button class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 px-3 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50" type="submit">
                                        <i class="h-4 w-4" data-lucide="calendar" aria-hidden="true"></i>{{ __('Schedule a Visit') }}
                                    </button>
                                </form>

                                <form action="{{ $isCompared ? route('properties.compare.destroy', $listing) : route('properties.compare.store', $listing) }}" method="POST">
                                    @csrf
                                    @if ($isCompared) @method('DELETE') @endif
                                    <button class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold transition {{ $isCompared ? 'text-rose-600 hover:border-rose-400' : 'text-slate-700 hover:border-indigo-500 hover:text-indigo-600' }}" type="submit">
                                        {{ $isCompared ? __('Remove from compare') : __('Add to compare') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <a class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                href="{{ route('login', ['intended' => request()->fullUrl()]) }}">
                                <i class="h-4 w-4" data-lucide="log-in" aria-hidden="true"></i>{{ __('Log in to contact') }}
                            </a>
                        @endauth
                    </section>

                    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-amber-900">
                            <i class="h-4 w-4" data-lucide="triangle-alert" aria-hidden="true"></i>{{ __('Stay safe') }}
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-amber-800">{{ __('Never pay any booking amount before verifying the property. Visit in person and confirm ownership first.') }}</p>
                    </section>

                    <section id="share" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-data="{ copied: false }">
                        <h2 class="text-sm font-bold text-slate-950">{{ __('Share this property') }}</h2>
                        <div class="mt-4 flex gap-2">
                            @foreach ($shareLinks as $share)
                                <a href="{{ $share['url'] }}" target="_blank" rel="noopener" aria-label="{{ $share['label'] }}"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 transition hover:bg-indigo-600 hover:text-white">
                                    <x-website.social-icon :platform="$share['icon']" class="h-4 w-4" />
                                </a>
                            @endforeach
                            <button type="button" aria-label="{{ __('Copy link to this property') }}"
                                @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 transition hover:bg-indigo-600 hover:text-white">
                                <i class="h-4 w-4" data-lucide="link" aria-hidden="true"></i>
                            </button>
                            <span x-show="copied" x-cloak class="self-center text-sm font-medium text-emerald-600">{{ __('Copied!') }}</span>
                        </div>
                    </section>

                    @auth
                        <section x-show="reportOpen" x-cloak x-transition class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-sm font-bold text-slate-950">{{ __('Report or dispute') }}</h2>
                            <form class="space-y-2" action="{{ route('reports.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="reportable_type" value="listing">
                                <input type="hidden" name="reportable_id" value="{{ $listing->id }}">
                                <label class="sr-only" for="report-reason">{{ __('Reason') }}</label>
                                <select id="report-reason" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="reason">
                                    @foreach (['fake_listing', 'scam', 'wrong_information', 'inappropriate_content', 'other'] as $reason)
                                        <option value="{{ $reason }}">{{ str($reason)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                                <label class="sr-only" for="report-details">{{ __('Report details') }}</label>
                                <textarea id="report-details" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="details" placeholder="{{ __('Report details') }}"></textarea>
                                <button class="text-sm font-semibold text-rose-600">{{ __('Submit report') }}</button>
                            </form>
                            <form class="space-y-2 border-t border-slate-100 pt-3" action="{{ route('disputes.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="property_listing_id" value="{{ $listing->id }}">
                                <label class="sr-only" for="dispute-subject">{{ __('Dispute subject') }}</label>
                                <input id="dispute-subject" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="subject" placeholder="{{ __('Dispute subject') }}" required>
                                <label class="sr-only" for="dispute-description">{{ __('Dispute description') }}</label>
                                <textarea id="dispute-description" class="w-full rounded-lg border border-slate-200 p-2 text-sm" name="description" placeholder="{{ __('Describe the dispute') }}" required></textarea>
                                <button class="text-sm font-semibold text-indigo-600">{{ __('Open dispute') }}</button>
                            </form>

                            @if ($listing->owner_id && $listing->owner_id !== auth()->id())
                                <div class="border-t border-slate-100 pt-3">
                                    @if ($ownerBlock)
                                        <form action="{{ route('account.blocks.destroy', $ownerBlock) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="text-sm font-semibold text-slate-600">{{ __('Unblock this owner') }}</button>
                                        </form>
                                    @else
                                        <form action="{{ route('account.blocks.store', $listing->owner_id) }}" method="POST"
                                            data-confirm="{{ __("Block this owner? They won't be able to message you anymore.") }}">
                                            @csrf
                                            <button class="text-sm font-semibold text-slate-600">{{ __('Block this owner') }}</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endauth
                </aside>
            </div>
        </div>

        {{-- Lightbox --}}
        <div x-show="lightboxOpen" x-cloak @keydown.escape.window="lightboxOpen = false"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/90 p-4">
            <button type="button" @click="lightboxOpen = false" class="absolute right-4 top-4 rounded-lg p-2 text-white hover:bg-white/10" aria-label="{{ __('Close gallery') }}">
                <i class="h-6 w-6" data-lucide="x" aria-hidden="true"></i>
            </button>
            <button type="button" @click="activeImage = (activeImage - 1 + gallery.length) % gallery.length"
                class="absolute left-4 top-1/2 -translate-y-1/2 rounded-lg p-2 text-white hover:bg-white/10" aria-label="{{ __('Previous photo') }}">
                <i class="h-7 w-7" data-lucide="chevron-left" aria-hidden="true"></i>
            </button>
            <img :src="gallery[activeImage]" class="max-h-[85vh] max-w-full rounded-lg object-contain" alt="{{ $listing->title }}">
            <button type="button" @click="activeImage = (activeImage + 1) % gallery.length"
                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-lg p-2 text-white hover:bg-white/10" aria-label="{{ __('Next photo') }}">
                <i class="h-7 w-7" data-lucide="chevron-right" aria-hidden="true"></i>
            </button>
            <p class="absolute bottom-4 text-sm font-medium text-white" x-text="(activeImage + 1) + ' / ' + gallery.length"></p>
        </div>

        {{-- Mobile sticky CTA --}}
        <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-2 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
            <p class="min-w-0 break-words text-lg font-bold text-indigo-600">
                {{ moneyFrom($listing->price, $listing->currency_code) }}@if ($priceSuffix)<span class="text-xs font-normal text-slate-500"> {{ $priceSuffix }}</span>@endif
            </p>
            @auth
                <a href="#contact" class="shrink-0 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Contact owner') }}</a>
            @else
                <a href="{{ route('login', ['intended' => request()->fullUrl()]) }}" class="shrink-0 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Log in to contact') }}</a>
            @endauth
        </div>
    </main>
@endsection
