@props([
    'eyebrow',
    'heading',
    'description',
])

<main class="relative min-h-screen overflow-hidden bg-linear-to-br from-white via-slate-50 to-indigo-50/70 text-slate-950">
    <div class="pointer-events-none absolute -left-32 top-1/3 h-96 w-96 rounded-full bg-indigo-200/25 blur-3xl" aria-hidden="true"></div>

    <div class="relative grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(460px,0.9fr)]">
        <section class="relative hidden overflow-hidden lg:flex lg:min-h-screen lg:flex-col lg:p-10 xl:p-14">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <img
                    class="absolute inset-0 h-full w-full object-contain object-right opacity-65 xl:opacity-75"
                    src="{{ asset('assets/images/website/auth/property-welcome-v2.webp') }}"
                    alt=""
                >
                <div class="absolute inset-0 bg-linear-to-r from-white/85 from-0% via-white/15 via-30% to-transparent to-55%"></div>
                <div class="absolute inset-0 bg-linear-to-l from-slate-50 from-0% via-slate-50/15 via-10% to-transparent to-25%"></div>
                <div class="absolute inset-0 bg-linear-to-b from-white/35 via-transparent to-indigo-50/25"></div>
            </div>

            <a class="relative z-10 w-fit" href="{{ route('home') }}" aria-label="{{ __('Rentdo home') }}">
                <x-application-logo class="h-8 w-auto" />
            </a>

            <div class="relative z-10 my-auto max-w-xl py-12">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/65 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm backdrop-blur-sm">
                    <i class="h-4 w-4 text-indigo-600" data-lucide="badge-check"></i>
                    {{ $eyebrow }}
                </span>
                <h1 class="mt-6 text-4xl font-semibold leading-tight tracking-tight text-slate-950 xl:text-5xl">{{ $heading }}</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-slate-500 xl:text-lg">{{ $description }}</p>

            </div>
        </section>

        <section class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-10 sm:px-8 lg:justify-start lg:px-12 xl:px-20">
            <div class="pointer-events-none absolute inset-0 lg:hidden" aria-hidden="true">
                <img
                    class="h-full w-full object-cover object-center opacity-[0.06]"
                    src="{{ asset('assets/images/website/auth/property-welcome-v2.webp') }}"
                    alt=""
                >
                <div class="absolute inset-0 bg-linear-to-b from-white/80 via-slate-50/75 to-indigo-50/80"></div>
            </div>

            <div class="relative z-10 w-full max-w-md">
                <div class="mb-10 flex items-center justify-between lg:hidden">
                    <a href="{{ route('home') }}" aria-label="{{ __('Rentdo home') }}">
                        <x-application-logo class="h-9 w-auto" />
                    </a>
                    <a class="text-sm font-semibold text-slate-500 transition hover:text-slate-950" href="{{ route('home') }}">{{ __('Back home') }}</a>
                </div>

                {{ $slot }}

                <p class="mt-8 text-center text-xs text-slate-400">© {{ date('Y') }} {{ __('Rentdo. Secure access to your property journey.') }}</p>
            </div>
        </section>
    </div>
</main>
