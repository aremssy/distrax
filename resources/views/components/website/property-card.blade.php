@props(['listing'])

@php
    $cover = $listing->coverImage->first();
    $coverUrl = match (true) {
        ! $cover => asset('assets/images/website/features/flat-1.webp'),
        $cover->disk === 'remote' || \Illuminate\Support\Str::startsWith($cover->path, ['http://', 'https://']) => $cover->path,
        default => \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path),
    };
    $priceSuffix = \App\Enums\PropertyType::tryFrom($listing->type)?->priceSuffix();
    $isFavorited = (bool) ($listing->is_favorited ?? false);
    $isNew = (bool) $listing->published_at?->gt(now()->subDays(7));
    $facts = array_filter([
        $listing->bedrooms !== null ? ['icon' => 'bed-double', 'value' => $listing->bedrooms.' Beds'] : null,
        $listing->bathrooms !== null ? ['icon' => 'bath', 'value' => $listing->bathrooms.' Baths'] : null,
        $listing->area_sqft ? ['icon' => 'maximize', 'value' => number_format($listing->area_sqft).' sqft'] : null,
    ]);
@endphp

<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
        <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $coverUrl }}"
            alt="{{ $listing->title }}" loading="lazy" width="640" height="480">
        <div class="absolute inset-x-0 top-0 z-10 flex items-start justify-between gap-1.5 p-2.5 sm:gap-2 sm:p-3">
            <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur sm:px-2.5 sm:text-[11px]">
                    {{ $listing->type === 'sale' ? 'For sale' : ucfirst($listing->type) }}
                </span>
                @if ($listing->relationLoaded('verificationCase') ? $listing->verificationCase : $listing->is_verified)
                    <x-verification-badge :status="$listing->verificationCase?->status ?? ($listing->is_verified ? 'distrax_verified' : 'in_progress')" />
                @endif
            </div>
            @auth
                <form method="POST" data-purpose="card-favorite-toggle"
                    action="{{ $isFavorited ? route('properties.favorite.destroy', $listing) : route('properties.favorite.store', $listing) }}">
                    @csrf
                    @if ($isFavorited)
                        @method('DELETE')
                    @endif
                    <button type="submit" aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
                        aria-label="{{ $isFavorited ? 'Remove '.$listing->title.' from favorites' : 'Save '.$listing->title.' to favorites' }}"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur transition hover:scale-110 {{ $isFavorited ? 'text-rose-500' : 'text-slate-600 hover:text-rose-500' }}">
                        <i class="h-4.5 w-4.5 {{ $isFavorited ? 'fill-current' : '' }}" data-lucide="heart" aria-hidden="true"></i>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" aria-label="Log in to save {{ $listing->title }}"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-sm backdrop-blur transition hover:scale-110 hover:text-rose-500">
                    <i class="h-4.5 w-4.5" data-lucide="heart" aria-hidden="true"></i>
                </a>
            @endauth
        </div>
        @if ($listing->is_featured)
            <span class="absolute bottom-3 start-3 flex items-center gap-1 rounded-full bg-amber-400 px-2.5 py-1 text-[11px] font-bold uppercase text-amber-950">
                <i class="h-3 w-3" data-lucide="star" aria-hidden="true"></i>Featured
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xl font-bold text-indigo-600">
                {{ moneyFrom($listing->price, $listing->currency_code) }}@if ($priceSuffix)<span class="text-xs font-normal text-slate-500"> {{ $priceSuffix }}</span>@endif
            </p>
            @if ($isNew)
                <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">New</span>
            @endif
        </div>
        <h3 class="mt-1.5 text-base font-bold text-slate-950 dark:text-white">
            <a href="{{ route('properties.show', $listing) }}"
                class="line-clamp-1 transition-colors after:absolute after:inset-0 group-hover:text-indigo-600">
                {{ $listing->title }}
            </a>
        </h3>
        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
            <i class="h-3.5 w-3.5 shrink-0 text-indigo-500" data-lucide="map-pin" aria-hidden="true"></i>
            <span class="line-clamp-1">{{ $listing->zone?->name ?? $listing->address ?? 'Location available on request' }}</span>
        </p>
        @if ($facts !== [])
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-sm font-medium text-slate-600 dark:border-slate-800 dark:text-slate-300">
                @foreach ($facts as $fact)
                    <span class="flex items-center gap-1.5">
                        <i class="h-4 w-4 text-indigo-500" data-lucide="{{ $fact['icon'] }}" aria-hidden="true"></i>{{ $fact['value'] }}
                    </span>
                @endforeach
            </div>
        @endif
        <div class="mt-auto">
            <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <span class="flex min-w-0 items-center gap-2">
                    @if ($listing->owner?->avatar)
                        <img class="h-7 w-7 shrink-0 rounded-full object-cover" src="{{ asset('storage/'.$listing->owner->avatar) }}"
                            alt="" loading="lazy" width="28" height="28">
                    @else
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                            aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($listing->owner?->name ?? 'P', 0, 1)) }}</span>
                    @endif
                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $listing->owner?->name ?? 'Property owner' }}</span>
                </span>
                @if ($listing->published_at)
                    <time class="shrink-0 text-xs text-slate-500" datetime="{{ $listing->published_at->toDateString() }}">
                        {{ $listing->published_at->diffForHumans() }}
                    </time>
                @endif
            </div>
        </div>
    </div>
</article>
