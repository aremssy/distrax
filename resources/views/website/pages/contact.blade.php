@extends('website.layouts.master')

@section('title', __('Contact Us').' | '.config('app.name'))

@php
    $officeLat = setting('office_lat');
    $officeLng = setting('office_lng');
    $officeAddress = setting('site_address');
    $googleMapsKey = setting('google_maps_api_key');
    $showMap = $officeLat && $officeLng && $googleMapsKey && ! ($lowDataMode ?? false);
    $mapKeyMissing = $officeLat && $officeLng && ! $googleMapsKey && ! ($lowDataMode ?? false);

    $contactDetails = array_values(array_filter([
        setting('site_phone') ? [
            'icon' => 'phone',
            'label' => __('Phone'),
            'value' => setting('site_phone'),
            'href' => 'tel:'.preg_replace('/[^0-9+]/', '', setting('site_phone')),
            'note' => setting('support_hours'),
        ] : null,
        setting('site_email') ? [
            'icon' => 'mail',
            'label' => __('Email'),
            'value' => setting('site_email'),
            'href' => 'mailto:'.setting('site_email'),
            'note' => setting('support_reply_time'),
        ] : null,
        $officeAddress ? [
            'icon' => 'map-pin',
            'label' => __('Office'),
            'value' => $officeAddress,
            'href' => null,
            'note' => null,
        ] : null,
    ]));

    $quickLinks = [
        ['icon' => 'circle-help', 'title' => __('FAQ'), 'body' => __('Find answers to the most commonly asked questions.'), 'cta' => __('View FAQ'), 'url' => route('faq')],
        ['icon' => 'newspaper', 'title' => __('Blog'), 'body' => __('Read guides and updates from our property team.'), 'cta' => __('Read the blog'), 'url' => route('blog.index')],
        ['icon' => 'users', 'title' => __('Talk to an Agent'), 'body' => __('Browse trusted agents and contact them directly.'), 'cta' => __('Find an agent'), 'url' => route('agents.index')],
        ['icon' => 'building-2', 'title' => __('Agencies'), 'body' => __('Work with an established property agency.'), 'cta' => __('View agencies'), 'url' => route('agencies.index')],
    ];
@endphp


@section('content')
    <main class="min-h-screen bg-slate-50 py-10 sm:py-14" data-purpose="contact-page">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <nav aria-label="Breadcrumb" class="mb-8">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <li><a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li><a class="transition hover:text-indigo-600" href="{{ route('faq') }}">{{ __('Support') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li class="font-semibold text-slate-950" aria-current="page">{{ __('Contact Us') }}</li>
                </ol>
            </nav>

            <div class="grid items-start gap-8 lg:grid-cols-[1fr_1.25fr]">
                <div>
                    <span class="inline-flex rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600">{{ __('Contact Us') }}</span>
                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                        {{ __("We're Here to Help You") }}
                    </h1>
                    <p class="mt-4 max-w-md text-base leading-7 text-slate-600">
                        {{ __("Have a question or need assistance? Our team is ready to help. Send us a message and we'll get back to you as soon as possible.") }}
                    </p>

                    <div class="mt-8 space-y-5">
                        @foreach ($contactDetails as $detail)
                            <div class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                    <i class="h-5 w-5" data-lucide="{{ $detail['icon'] }}" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0">
                                    <h2 class="text-sm font-bold text-slate-950">{{ $detail['label'] }}</h2>
                                    @if ($detail['href'])
                                        <a class="mt-0.5 block break-words text-sm text-slate-700 transition hover:text-indigo-600" href="{{ $detail['href'] }}">{{ $detail['value'] }}</a>
                                    @else
                                        <p class="mt-0.5 break-words text-sm text-slate-700">{{ $detail['value'] }}</p>
                                    @endif
                                    @if ($detail['note'])
                                        <p class="mt-0.5 text-sm text-slate-500">{{ $detail['note'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ __('Send us a message') }}</h2>
                    <p class="mt-1.5 text-sm text-slate-600">{{ __("Fill out the form below and we'll get back to you.") }}</p>

                    <form action="{{ route('contact.store') }}" method="POST" class="mt-6 grid gap-5">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <label class="text-sm font-medium text-slate-700" for="name">{{ __('Full Name') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="{{ __('Enter your name') }}" required
                                    class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            </div>
                            <div class="grid gap-2">
                                <label class="text-sm font-medium text-slate-700" for="email">{{ __('Email Address') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('Enter your email') }}" required
                                    class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-slate-700" for="phone">{{ __('Phone') }} <span class="text-slate-500">({{ __('optional') }})</span></label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+880 1234 567890"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-slate-700" for="subject">{{ __('Subject') }}</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" placeholder="{{ __('Enter subject') }}" required
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium text-slate-700" for="message">{{ __('Message') }}</label>
                            <textarea id="message" name="message" rows="5" placeholder="{{ __('Write your message...') }}" required
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit"
                            class="group flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 active:scale-[0.99]">
                            <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>
                            {{ __('Send Message') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" data-purpose="contact-quick-links">
                @foreach ($quickLinks as $link)
                    <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <i class="h-5 w-5" data-lucide="{{ $link['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <h2 class="mt-5 text-base font-bold text-slate-950">{{ $link['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $link['body'] }}</p>
                        <a href="{{ $link['url'] }}"
                            class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 transition after:absolute after:inset-0 group-hover:gap-2.5 group-hover:text-indigo-700">
                            {{ $link['cta'] }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($officeAddress)
                <section class="mt-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-purpose="office-map">
                    <div class="relative">
                        @if ($showMap)
                            <div id="office-map" class="h-[420px] w-full bg-slate-100"
                                data-lat="{{ $officeLat }}" data-lng="{{ $officeLng }}"
                                role="application" aria-label="{{ __('Map showing our office location') }}"></div>
                        @elseif ($lowDataMode ?? false)
                            <div class="flex h-56 w-full items-center justify-center bg-slate-100 text-sm text-slate-600">
                                {{ __('Map is hidden in low-data mode') }}
                            </div>
                        @elseif ($mapKeyMissing)
                            <div class="flex h-56 w-full flex-col items-center justify-center gap-2 bg-slate-100 px-6 text-center">
                                <i class="h-6 w-6 text-slate-400" data-lucide="map-pinned" aria-hidden="true"></i>
                                <p class="text-sm font-semibold text-slate-600">{{ __('Map unavailable') }}</p>
                                <p class="text-xs text-slate-500">{{ __('Google Maps has not been configured for this site.') }}</p>
                                @if (auth()->check() && auth()->user()->isAdmin())
                                    <p class="mt-1 text-xs font-semibold text-indigo-600">{{ __('Admin: add a Google Maps API key in Settings → General.') }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[400] p-4 sm:inset-y-0 sm:right-auto sm:w-80 sm:p-6">
                            <div class="pointer-events-auto rounded-2xl bg-white p-5 shadow-xl sm:mt-6">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                    <i class="h-5 w-5" data-lucide="map-pin" aria-hidden="true"></i>
                                </span>
                                <h2 class="mt-4 text-base font-bold text-slate-950">{{ __(':site Office', ['site' => setting('site_name', config('app.name'))]) }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $officeAddress }}</p>
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($officeLat && $officeLng ? $officeLat.','.$officeLng : $officeAddress) }}"
                                    target="_blank" rel="noopener"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                    <i class="h-4 w-4" data-lucide="navigation" aria-hidden="true"></i>
                                    {{ __('Get Directions') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="mt-10 rounded-2xl bg-indigo-50/70 p-6 sm:p-8">
                <div class="grid gap-8 lg:grid-cols-[1.3fr_auto_1fr] lg:items-center">
                    <div class="flex items-center gap-6">
                        <span class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm sm:flex" aria-hidden="true">
                            <i class="h-8 w-8" data-lucide="mail-open"></i>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-950">{{ __("Can't find what you're looking for?") }}</h2>
                            <p class="mt-1.5 text-sm text-slate-600">{{ __('Our support team is ready to assist you personally.') }}</p>
                            @if (setting('site_email'))
                                <a href="mailto:{{ setting('site_email') }}"
                                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    {{ __('Email Support') }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <span class="hidden h-full w-px bg-indigo-200 lg:block" aria-hidden="true"></span>

                    <div class="space-y-4">
                        @foreach ([[__('Fast Response'), setting('support_reply_time', __('We typically reply within a few hours.'))], [__('Friendly Support'), __('Our team is always happy to help.')]] as [$title, $body])
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm">
                                    <i class="h-4 w-4" data-lucide="check" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-950">{{ $title }}</h3>
                                    <p class="text-sm text-slate-600">{{ $body }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-sm text-slate-600" data-purpose="contact-feedback">
                @if (session('feedback_success'))
                    <p class="flex items-center gap-2 font-semibold text-emerald-600">
                        <i class="h-4 w-4" data-lucide="check" aria-hidden="true"></i>{{ session('feedback_success') }}
                    </p>
                @else
                    <span>{{ __('Was this page helpful?') }}</span>
                    @foreach ([[__('Yes'), 1], [__('No'), 0]] as [$label, $value])
                        <form action="{{ route('feedback.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="page" value="contact">
                            <input type="hidden" name="is_helpful" value="{{ $value }}">
                            <button type="submit"
                                class="rounded-lg border border-slate-200 bg-white px-5 py-2 font-semibold text-slate-700 shadow-sm transition hover:border-indigo-500 hover:text-indigo-600">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>
    </main>
@endsection

@if ($showMap)
    @push('scripts')
        <script data-purpose="office-map-init">
            function initOfficeMap() {
                const officeMapEl = document.getElementById('office-map');
                if (!officeMapEl || !window.google?.maps) {
                    return;
                }

                const position = {
                    lat: parseFloat(officeMapEl.dataset.lat),
                    lng: parseFloat(officeMapEl.dataset.lng),
                };

                const map = new google.maps.Map(officeMapEl, {
                    center: position,
                    zoom: 15,
                    scrollwheel: false,
                });

                const marker = new google.maps.Marker({ position, map });
                const infoWindow = new google.maps.InfoWindow({ content: @json($officeAddress) });

                marker.addListener('click', () => infoWindow.open({ anchor: marker, map }));
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&callback=initOfficeMap&loading=async" async defer></script>
    @endpush
@endif
