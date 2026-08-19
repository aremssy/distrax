<section class="mx-auto my-16 max-w-7xl px-4 md:px-6" data-purpose="app-banner" data-reveal>
    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="relative grid min-h-[420px] items-center gap-10 px-6 py-10 sm:px-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(280px,0.85fr)] lg:px-16 lg:py-14">
            <div class="relative z-10 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-600" data-reveal-item>{{ __('Rentdo mobile app') }}</p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl" data-reveal-item>
                    {{ __('Find your next property anywhere.') }}
                </h2>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg" data-reveal-item>
                    {{ __('Search listings, save favorites, and connect with trusted agents wherever your day takes you.') }}
                </p>

                <div class="mt-7 flex flex-wrap gap-3" data-reveal-item>
                    @foreach ([['search', __('Smart property search')], ['heart', __('Save your favorites')], ['message-circle', __('Connect with agents')]] as [$icon, $label])
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700">
                            <i class="h-4 w-4 text-indigo-600" data-lucide="{{ $icon }}"></i>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>

                @if (setting('app_store_url') || setting('play_store_url'))
                    <div class="mt-8 flex flex-wrap items-center gap-3" data-purpose="app-store-links" data-reveal-item>
                        @if (setting('app_store_url'))
                            <a href="{{ setting('app_store_url') }}" target="_blank" rel="noopener"
                                class="flex h-14 w-[150px] items-center justify-center transition duration-200 hover:-translate-y-0.5 hover:drop-shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-600 sm:h-16 sm:w-[172px]">
                                <img loading="lazy" decoding="async" width="172" height="64" src="{{ asset('assets/images/website/download/app-store-badge-v3.webp') }}"
                                    alt="Download on the App Store" class="block h-full w-full object-contain">
                            </a>
                        @endif
                        @if (setting('play_store_url'))
                            <a href="{{ setting('play_store_url') }}" target="_blank" rel="noopener"
                                class="flex h-14 w-[150px] items-center justify-center transition duration-200 hover:-translate-y-0.5 hover:drop-shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-600 sm:h-16 sm:w-[172px]">
                                <img loading="lazy" decoding="async" width="172" height="64" src="{{ asset('assets/images/website/download/google-play-badge-v5.webp') }}"
                                    alt="Download on Google Play" class="block h-full w-full object-contain">
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="relative hidden h-full min-h-[330px] md:block" aria-hidden="true">
                <img loading="lazy" decoding="async" width="288" height="576" src="{{ home_image('home_app_phone_image', 'assets/images/website/download/phone.webp') }}" alt="" data-float
                    class="absolute -bottom-28 left-1/2 w-64 -translate-x-1/2 drop-shadow-[0_28px_45px_rgba(30,41,59,0.22)] lg:-bottom-36 lg:w-72">
            </div>
        </div>
    </div>
</section>
