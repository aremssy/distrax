@extends('website.layouts.master')

@section('title', $agency->name.' | Agencies')

@push('head')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($agency->description ?? $agency->name), 155) }}">
@endpush

@section('content')
    @php
        $logoUrl = $agency->logo ? asset('storage/'.$agency->logo) : null;
        $initials = \Illuminate\Support\Str::of($agency->name)->explode(' ')->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('');
        $ratingValue = number_format($reviewsAverage, 1);

        $stats = collect([
            ['icon' => 'building-2', 'label' => __('Active listings'), 'value' => number_format($agency->listings_count)],
            ['icon' => 'users', 'label' => __('Agents'), 'value' => number_format($agency->agents_count)],
            ['icon' => 'star', 'label' => __('Avg rating'), 'value' => $agency->reviews_count ? $ratingValue : '—'],
            ['icon' => 'message-square-quote', 'label' => __('Reviews'), 'value' => number_format($agency->reviews_count)],
        ]);

        $contacts = collect([
            $agency->phone ? ['icon' => 'phone', 'label' => $agency->phone, 'href' => 'tel:'.$agency->phone] : null,
            $agency->email ? ['icon' => 'mail', 'label' => $agency->email, 'href' => 'mailto:'.$agency->email] : null,
            $agency->website ? ['icon' => 'globe', 'label' => preg_replace('#^https?://#', '', rtrim($agency->website, '/')), 'href' => $agency->website, 'external' => true] : null,
            ($agency->address || $agency->zone) ? ['icon' => 'map-pin', 'label' => trim(($agency->address ?? '').($agency->address && $agency->zone ? ', ' : '').($agency->zone?->name ?? ''), ', ')] : null,
        ])->filter()->values();

        $tabs = collect([
            'listings' => __('Listings (:count)', ['count' => $agency->listings_count]),
            'agents' => __('Agents (:count)', ['count' => $agency->agents_count]),
            'reviews' => __('Reviews (:count)', ['count' => $agency->reviews_count]),
        ]);
    @endphp

    <main class="min-h-screen bg-slate-50 pb-16 pt-6" data-purpose="agency-details-page"
        x-data="{ tab: 'listings' }">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            {{-- Breadcrumb --}}
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-600" aria-label="Breadcrumb">
                <a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a>
                <i class="h-3.5 w-3.5 text-slate-400" data-lucide="chevron-right" aria-hidden="true"></i>
                <a class="transition hover:text-indigo-600" href="{{ route('agencies.index') }}">{{ __('Agencies') }}</a>
                <i class="h-3.5 w-3.5 text-slate-400" data-lucide="chevron-right" aria-hidden="true"></i>
                <span class="max-w-64 truncate font-semibold text-slate-950">{{ $agency->name }}</span>
            </nav>

            {{-- ── Hero ─────────────────────────────────────────────────────── --}}
            <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="h-28 bg-linear-to-r from-indigo-600 via-indigo-500 to-violet-500 sm:h-32"></div>
                <div class="px-5 pb-6 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
                            <div class="-mt-12 flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-white shadow-md sm:h-28 sm:w-28">
                                @if ($logoUrl)
                                    <img class="h-full w-full object-contain" src="{{ $logoUrl }}" alt="{{ $agency->name }} logo" width="112" height="112">
                                @else
                                    <span class="flex h-full w-full items-center justify-center bg-indigo-50 text-2xl font-bold text-indigo-600">{{ $initials }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-2xl font-bold text-slate-950 sm:text-3xl">{{ $agency->name }}</h1>
                                    @if ($agency->is_verified)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600">
                                            <i class="h-3.5 w-3.5" data-lucide="badge-check" aria-hidden="true"></i>{{ __('Verified') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-600">
                                    @if ($agency->reviews_count)
                                        <span class="flex items-center gap-1.5 font-semibold text-slate-900">
                                            <i class="h-4 w-4 fill-current text-amber-400" data-lucide="star" aria-hidden="true"></i>
                                            {{ $ratingValue }}
                                            <span class="font-normal text-slate-500">({{ __(':count reviews', ['count' => $agency->reviews_count]) }})</span>
                                        </span>
                                    @endif
                                    @if ($agency->address || $agency->zone)
                                        <span class="flex items-center gap-1.5">
                                            <i class="h-4 w-4 shrink-0 text-indigo-500" data-lucide="map-pin" aria-hidden="true"></i>
                                            {{ trim(($agency->address ?? '').(($agency->address && $agency->zone) ? ', ' : '').($agency->zone?->name ?? ''), ', ') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($agency->phone)
                                <a href="tel:{{ $agency->phone }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    <i class="h-4 w-4" data-lucide="phone" aria-hidden="true"></i>{{ __('Call now') }}
                                </a>
                            @endif
                            @if ($agency->email)
                                <a href="mailto:{{ $agency->email }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">
                                    <i class="h-4 w-4" data-lucide="mail" aria-hidden="true"></i>{{ __('Email') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.9fr)_minmax(300px,1fr)] lg:items-start">
                {{-- ── Main column ──────────────────────────────────────────── --}}
                <div class="space-y-8">
                    @if ($agency->description)
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-bold text-slate-950">{{ __('About :name', ['name' => $agency->name]) }}</h2>
                            <p class="mt-3 whitespace-pre-line text-[15px] leading-7 text-slate-600">{{ $agency->description }}</p>
                        </section>
                    @endif

                    {{-- Tabbed content --}}
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 px-4" role="tablist" aria-label="Agency sections">
                            @foreach ($tabs as $key => $label)
                                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                    class="shrink-0 border-b-2 px-4 py-4 text-sm font-semibold transition"
                                    :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-600 hover:text-indigo-600'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </nav>

                        {{-- Listings --}}
                        <div class="p-6" x-show="tab === 'listings'">
                            @if ($agency->listings->isEmpty())
                                <div class="py-10 text-center">
                                    <i class="mx-auto h-10 w-10 text-slate-300" data-lucide="building-2" aria-hidden="true"></i>
                                    <p class="mt-3 text-sm text-slate-600">{{ __('This agency has no active listings right now.') }}</p>
                                </div>
                            @else
                                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($agency->listings as $listing)
                                        <x-website.property-card :listing="$listing" />
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Agents --}}
                        <div class="p-6" x-show="tab === 'agents'" x-cloak>
                            @if ($agency->agents->isEmpty())
                                <div class="py-10 text-center">
                                    <i class="mx-auto h-10 w-10 text-slate-300" data-lucide="users" aria-hidden="true"></i>
                                    <p class="mt-3 text-sm text-slate-600">{{ __('No agents listed for this agency yet.') }}</p>
                                </div>
                            @else
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @foreach ($agency->agents as $agent)
                                        <a href="{{ route('agents.show', $agent) }}"
                                            class="flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                                            @if ($agent->user->avatar)
                                                <img class="h-14 w-14 shrink-0 rounded-full object-cover" src="{{ asset('storage/'.$agent->user->avatar) }}"
                                                    alt="{{ $agent->user->name }}" width="56" height="56">
                                            @else
                                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-lg font-bold text-indigo-600" aria-hidden="true">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($agent->user->name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-slate-900">{{ $agent->user->name }}</p>
                                                <p class="truncate text-sm text-slate-500">{{ $agent->title ?? __('Property agent') }}</p>
                                                <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-indigo-600">
                                                    <i class="h-3.5 w-3.5" data-lucide="building-2" aria-hidden="true"></i>
                                                    {{ __(':count :label', ['count' => $agent->listings_count, 'label' => \Illuminate\Support\Str::plural('listing', $agent->listings_count)]) }}
                                                </p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Reviews --}}
                        <div class="p-6" x-show="tab === 'reviews'" x-cloak>
                            @if ($agency->reviews->isEmpty())
                                <div class="py-10 text-center">
                                    <i class="mx-auto h-10 w-10 text-slate-300" data-lucide="message-square-quote" aria-hidden="true"></i>
                                    <p class="mt-3 text-sm text-slate-600">{{ __('No reviews yet for this agency.') }}</p>
                                </div>
                            @else
                                <div class="flex items-center gap-4 rounded-xl bg-slate-50 p-5">
                                    <div class="text-center">
                                        <p class="text-3xl font-extrabold text-slate-950">{{ $ratingValue }}</p>
                                        <p class="mt-1 text-amber-400" aria-hidden="true">{{ str_repeat('★', (int) round($reviewsAverage)) }}<span class="text-slate-300">{{ str_repeat('★', 5 - (int) round($reviewsAverage)) }}</span></p>
                                    </div>
                                    <div class="border-l border-slate-200 pl-4 text-sm text-slate-600">
                                        {{ __('Based on :count verified :label.', ['count' => $agency->reviews_count, 'label' => \Illuminate\Support\Str::plural('review', $agency->reviews_count)]) }}
                                    </div>
                                </div>

                                <div class="mt-2 divide-y divide-slate-100">
                                    @foreach ($agency->reviews as $review)
                                        <article class="flex gap-4 py-5">
                                            @if ($review->reviewer?->avatar)
                                                <img class="h-10 w-10 shrink-0 rounded-full object-cover" src="{{ asset('storage/'.$review->reviewer->avatar) }}" alt="" width="40" height="40">
                                            @else
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600" aria-hidden="true">
                                                    {{ \Illuminate\Support\Str::of($review->reviewer?->name ?? 'User')->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') }}
                                                </span>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                    <p class="font-bold text-slate-900">{{ $review->reviewer?->name ?? __('Verified user') }}</p>
                                                    <time class="text-xs text-slate-500">{{ $review->created_at->format('M j, Y') }}</time>
                                                    <p class="text-sm text-amber-400" aria-label="{{ __(':rating out of 5 stars', ['rating' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                                                </div>
                                                @if ($review->body)
                                                    <p class="mt-1.5 text-[15px] leading-6 text-slate-600">{{ $review->body }}</p>
                                                @endif
                                                @if ($review->owner_reply)
                                                    <div class="mt-3 rounded-lg border-l-2 border-indigo-200 bg-indigo-50/50 px-4 py-3">
                                                        <p class="text-xs font-semibold text-indigo-700">{{ __('Reply from :name', ['name' => $agency->name]) }}</p>
                                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $review->owner_reply }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Sidebar ──────────────────────────────────────────────── --}}
                <aside class="space-y-5 lg:sticky lg:top-28">
                    {{-- Stats --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-sm font-bold text-slate-950">{{ __('Agency at a glance') }}</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            @foreach ($stats as $stat)
                                <div class="flex flex-col items-center gap-1.5 rounded-xl bg-slate-50 px-2 py-4 text-center">
                                    <i class="h-5 w-5 text-indigo-500" data-lucide="{{ $stat['icon'] }}" aria-hidden="true"></i>
                                    <dd class="text-lg font-bold text-slate-950">{{ $stat['value'] }}</dd>
                                    <dt class="text-xs text-slate-500">{{ $stat['label'] }}</dt>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    {{-- Contact --}}
                    @if ($contacts->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-bold text-slate-950">{{ __('Contact information') }}</h2>
                            <ul class="mt-4 space-y-3 text-sm">
                                @foreach ($contacts as $contact)
                                    <li>
                                        @if (isset($contact['href']))
                                            <a href="{{ $contact['href'] }}" @if ($contact['external'] ?? false) target="_blank" rel="noopener" @endif
                                                class="flex items-start gap-3 text-slate-600 transition hover:text-indigo-600">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                                    <i class="h-4 w-4" data-lucide="{{ $contact['icon'] }}" aria-hidden="true"></i>
                                                </span>
                                                <span class="wrap-break-word pt-1.5 font-medium">{{ $contact['label'] }}</span>
                                            </a>
                                        @else
                                            <div class="flex items-start gap-3 text-slate-600">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                                    <i class="h-4 w-4" data-lucide="{{ $contact['icon'] }}" aria-hidden="true"></i>
                                                </span>
                                                <span class="wrap-break-word pt-1.5 font-medium">{{ $contact['label'] }}</span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            @if ($agency->phone)
                                <a href="tel:{{ $agency->phone }}"
                                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    <i class="h-4 w-4" data-lucide="phone" aria-hidden="true"></i>{{ __('Contact agency') }}
                                </a>
                            @endif
                        </section>
                    @endif

                    {{-- Safety --}}
                    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-amber-900">
                            <i class="h-4 w-4" data-lucide="shield-check" aria-hidden="true"></i>{{ __('Deal safely') }}
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-amber-800">{{ __('Always visit properties in person and verify ownership before making any payment.') }}</p>
                    </section>
                </aside>
            </div>
        </div>
    </main>
@endsection
