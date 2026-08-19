@extends('website.layouts.master')

@section('title', __('Frequently Asked Questions').' | '.config('app.name'))

@push('head')
    <meta name="description" content="{{ __('Answers to common questions about listings, payments, bookings, and your account on :app.', ['app' => config('app.name')]) }}">
    <link rel="canonical" href="{{ route('faq') }}">
    @if ($allFaqs->isNotEmpty())
        @php
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $allFaqs->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
                ])->values(),
            ];
        @endphp
        {{-- JSON_HEX_TAG escapes < and >, so content cannot close this script tag. --}}
        <script type="application/ld+json">{!! json_encode($faqSchema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endpush

@section('content')
    @php
        $categoryIcons = [
            'general' => 'house',
            'property' => 'building-2',
            'properties' => 'building-2',
            'listing' => 'file-text',
            'listings' => 'file-text',
            'booking' => 'calendar-check',
            'bookings' => 'calendar-check',
            'payment' => 'credit-card',
            'payments' => 'credit-card',
            'account' => 'user-round',
            'support' => 'headphones',
        ];
    @endphp

    <main class="min-h-screen bg-white py-10 sm:py-14" data-purpose="faq-page">
        <div class="mx-auto max-w-5xl px-4 md:px-6">
            <nav aria-label="Breadcrumb" class="mb-8">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <li><a class="transition hover:text-indigo-600" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li><a class="transition hover:text-indigo-600" href="{{ route('contact') }}">{{ __('Support') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li class="font-semibold text-slate-950" aria-current="page">{{ __('FAQ') }}</li>
                </ol>
            </nav>

            <div class="grid items-center gap-10 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600">
                        <i class="h-4 w-4" data-lucide="file-question" aria-hidden="true"></i>{{ __('FAQ') }}
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                        {{ __('Frequently Asked Questions') }}
                    </h1>
                    <p class="mt-4 max-w-md text-base text-slate-600">
                        {{ __('Find answers to the most common questions about our platform, features and services.') }}
                    </p>
                </div>

                <div class="relative hidden justify-center lg:flex" aria-hidden="true">
                    <span class="absolute left-2 top-24 grid grid-cols-3 gap-2">
                        @for ($dot = 0; $dot < 9; $dot++)
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-200"></span>
                        @endfor
                    </span>
                    <span class="absolute right-6 top-0 h-7 w-7 rounded-full border-2 border-slate-200"></span>
                    <span class="relative mt-8 flex h-32 w-40 items-end justify-center rounded-3xl rounded-bl-none bg-slate-100 pb-6 shadow-sm">
                        <span class="flex gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                        </span>
                    </span>
                    <span class="absolute -top-2 right-10 flex h-36 w-36 items-center justify-center rounded-3xl rounded-br-none bg-indigo-500 text-5xl font-black text-white shadow-lg shadow-indigo-500/30">?</span>
                </div>
            </div>

            <form action="{{ route('faq') }}" method="GET" class="mt-10" data-purpose="faq-search" data-live-search="#faq-categories,#faq-results" role="search">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
                    <i class="ms-3 h-5 w-5 shrink-0 text-slate-500" data-lucide="search" aria-hidden="true"></i>
                    <label class="sr-only" for="faq-search">{{ __('Search the FAQ') }}</label>
                    <input id="faq-search" type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search for answers...') }}"
                        class="h-11 min-w-0 flex-1 border-0 bg-transparent text-base text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                    <button type="submit"
                        class="h-11 shrink-0 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        {{ __('Search') }}
                    </button>
                </div>
            </form>

            @if ($categoryCounts->isNotEmpty())
                <div id="faq-categories" class="mt-8 grid grid-cols-2 gap-4 transition-opacity duration-200 sm:grid-cols-3 lg:grid-cols-6" data-purpose="faq-categories">
                    @foreach ($categoryCounts as $category => $count)
                        @php $isActive = $search === '' && $category === $activeCategory; @endphp
                        <a href="{{ route('faq', ['category' => $category]) }}"
                            @if ($isActive) aria-current="true" @endif
                            class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 p-5 text-center transition hover:-translate-y-0.5 hover:shadow-md {{ $isActive ? 'border-indigo-500 bg-indigo-50/50 shadow-sm' : 'border-slate-200 bg-white' }}">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                <i class="h-5 w-5" data-lucide="{{ $categoryIcons[Str::lower($category)] ?? 'circle-help' }}" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-slate-950">{{ $category }}</span>
                                <span class="mt-0.5 block text-xs {{ $isActive ? 'font-semibold text-indigo-600' : 'text-slate-500' }}">
                                    {{ trans_choice(':count Question|:count Questions', $count, ['count' => $count]) }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div id="faq-results" class="transition-opacity duration-200">
            @forelse ($faqs as $category => $items)
                <section class="mt-12" aria-label="{{ $category }} questions">
                    <div class="text-center">
                        <h2 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $category }}</h2>
                        <p class="mt-1.5 text-sm text-slate-600">
                            @if ($search !== '')
                                {{ trans_choice(':count result|:count results', $items->count(), ['count' => $items->count()]) }} {{ __('for') }} &ldquo;{{ $search }}&rdquo;
                            @else
                                {{ trans_choice('One common question in :category.|:count common questions in :category.', $items->count(), ['count' => $items->count(), 'category' => $category]) }}
                            @endif
                        </p>
                    </div>

                    <div class="mt-6 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($items as $faq)
                            <details class="group" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[15px] font-bold text-slate-950 transition hover:bg-slate-50 group-open:text-indigo-600">
                                    {{ $faq->question }}
                                    <i class="h-4.5 w-4.5 shrink-0 text-slate-500 transition group-open:rotate-180 group-open:text-indigo-600"
                                        data-lucide="chevron-down" aria-hidden="true"></i>
                                </summary>
                                <div class="px-5 pb-5 text-[15px] leading-7 text-slate-600">{!! nl2br(e($faq->answer)) !!}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="mt-12 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <i class="mx-auto h-10 w-10 text-slate-500" data-lucide="circle-help" aria-hidden="true"></i>
                    @if ($search !== '')
                        <h2 class="mt-4 text-xl font-bold text-slate-950">{{ __('No answers matched ":search"', ['search' => $search]) }}</h2>
                        <p class="mx-auto mt-2 max-w-md text-base text-slate-600">{{ __('Try a different keyword, or browse the categories above.') }}</p>
                        <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700" href="{{ route('faq') }}">{{ __('Clear search') }}</a>
                    @else
                        <h2 class="mt-4 text-xl font-bold text-slate-950">{{ __('No questions published yet') }}</h2>
                        <p class="mx-auto mt-2 max-w-md text-base text-slate-600">{{ __("Our FAQ is being prepared. In the meantime, please reach out and we'll be glad to help.") }}</p>
                        <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700" href="{{ route('contact') }}">{{ __('Contact us') }}</a>
                    @endif
                </div>
            @endforelse
            </div>

            <section class="mt-12 rounded-2xl bg-indigo-50/70 p-6 sm:p-8" data-purpose="faq-support">
                <div class="grid gap-8 lg:grid-cols-[1.2fr_auto_1fr] lg:items-center">
                    <div class="flex items-center gap-6">
                        <span class="hidden h-24 w-24 shrink-0 items-center justify-center rounded-full bg-indigo-100/70 sm:flex" aria-hidden="true">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm">
                                <i class="h-7 w-7" data-lucide="headphones"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-950">{{ __('Still need help?') }}</h2>
                            <p class="mt-1.5 max-w-sm text-sm text-slate-600">{{ __("Can't find the answer you're looking for? Our support team is ready to help you.") }}</p>
                            <a href="{{ route('contact') }}"
                                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                {{ __('Contact Support') }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <span class="hidden h-full w-px bg-indigo-200 lg:block" aria-hidden="true"></span>

                    <div class="space-y-4">
                        @if (setting('site_email'))
                            <a href="mailto:{{ setting('site_email') }}" class="group flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                                    <i class="h-5 w-5" data-lucide="mail" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-950 group-hover:text-indigo-600">{{ __('Email Us') }}</span>
                                    <span class="block truncate text-sm text-slate-600">{{ setting('site_email') }}</span>
                                </span>
                            </a>
                        @endif
                        @if (setting('site_phone'))
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('site_phone')) }}" class="group flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                                    <i class="h-5 w-5" data-lucide="message-circle" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-950 group-hover:text-indigo-600">{{ __('Call Us') }}</span>
                                    <span class="block truncate text-sm text-slate-600">{{ setting('support_hours', __('Available 9AM - 6PM (Mon - Fri)')) }}</span>
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-sm text-slate-600" data-purpose="faq-feedback">
                @if (session('feedback_success'))
                    <p class="flex items-center gap-2 font-semibold text-emerald-600">
                        <i class="h-4 w-4" data-lucide="check" aria-hidden="true"></i>{{ session('feedback_success') }}
                    </p>
                @else
                    <span class="flex items-center gap-2">
                        <i class="h-4 w-4 text-slate-500" data-lucide="lightbulb" aria-hidden="true"></i>
                        {{ __('Help us improve this page. Was this helpful?') }}
                    </span>
                    @foreach ([[__('Yes'), 1], [__('No'), 0]] as [$label, $value])
                        <form action="{{ route('feedback.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="page" value="faq">
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
