@extends('website.layouts.master')

@section('title', $page->meta_title ?: $page->title.' | '.config('app.name'))

@push('head')
    @if ($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
    <link rel="canonical" href="{{ route('pages.show', $page->slug) }}">
@endpush

@php
    /**
     * Per-page hero dressing for the legal pages. A page not listed here still renders
     * (title, summary, contents, body) — it just gets no badge, artwork, or closing note.
     */
    $legalPages = [
        'privacy-policy' => [
            'badge' => __('Your Privacy Matters'),
            'badge_icon' => 'lock',
            'artwork' => 'shield-check',
            'note' => __('We are committed to protecting your privacy and building your trust.'),
        ],
        'terms-of-service' => [
            'badge' => null,
            'badge_icon' => null,
            'artwork' => 'scale',
            'note' => __('By using our platform, you acknowledge that you have read, understood, and agree to these Terms and Conditions.'),
        ],
        'refund-policy' => [
            'badge' => null,
            'badge_icon' => null,
            'artwork' => 'receipt',
            'note' => null,
        ],
    ];

    $legal = $legalPages[$page->slug] ?? null;
    $sectionGroup = $legal ? __('Legal') : __('Pages');
    $sections = $page->tableOfContents();

    /** Keyword → icon for the contents list; falls back to a generic file icon. */
    $sectionIcons = [
        'accept' => 'file-text', 'information we collect' => 'file-text', 'collect' => 'file-text',
        'service' => 'monitor', 'description' => 'monitor',
        'account' => 'user-round', 'children' => 'user-round',
        'responsib' => 'shield-check', 'security' => 'shield-check',
        'payment' => 'credit-card', 'fee' => 'credit-card',
        'intellectual' => 'copyright',
        'prohibit' => 'ban',
        'disclaimer' => 'circle-alert', 'warrant' => 'circle-alert',
        'liability' => 'scale',
        'terminat' => 'power',
        'governing' => 'globe', 'third-party' => 'link',
        'change' => 'pencil', 'update' => 'pencil',
        'contact' => 'mail',
        'cookie' => 'cookie',
        'rights' => 'settings-2', 'choice' => 'settings-2',
        'share' => 'share-2',
    ];

    $iconFor = function (string $title) use ($sectionIcons): string {
        $needle = Str::lower($title);

        foreach ($sectionIcons as $keyword => $icon) {
            if (str_contains($needle, $keyword)) {
                return $icon;
            }
        }

        return 'file-text';
    };
@endphp

@section('content')
    <main class="min-h-screen bg-slate-50" data-purpose="cms-page">
        <header class="border-b border-slate-200 bg-gradient-to-b from-indigo-50/60 to-slate-50">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:py-14 md:px-6">
                <nav aria-label="Breadcrumb" class="mb-8">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                        <li>
                            <a class="flex items-center gap-1.5 transition hover:text-indigo-600" href="{{ route('home') }}">
                                <i class="h-3.5 w-3.5" data-lucide="house" aria-hidden="true"></i>{{ __('Home') }}
                            </a>
                        </li>
                        <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                        <li>{{ $sectionGroup }}</li>
                        <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                        <li class="font-semibold text-indigo-600" aria-current="page">{{ $page->title }}</li>
                    </ol>
                </nav>

                <div class="grid items-center gap-8 lg:grid-cols-[1.3fr_0.7fr]">
                    <div>
                        @if ($legal && $legal['badge'])
                            <span class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 ring-1 ring-indigo-100">
                                <i class="h-4 w-4" data-lucide="{{ $legal['badge_icon'] }}" aria-hidden="true"></i>
                                {{ $legal['badge'] }}
                            </span>
                        @endif

                        <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                            {{ $page->title }}
                        </h1>

                        @if ($page->meta_description)
                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">{{ $page->meta_description }}</p>
                        @endif

                        <p class="mt-6 flex items-center gap-2 text-sm text-slate-500">
                            <i class="h-4 w-4 text-slate-500" data-lucide="calendar" aria-hidden="true"></i>
                            {{ __('Last updated: :date', ['date' => $page->updated_at?->format('F j, Y')]) }}
                        </p>
                    </div>

                    @if ($legal)
                        <div class="relative hidden justify-center lg:flex" aria-hidden="true">
                            <span class="relative flex h-40 w-32 flex-col justify-center gap-2 rounded-2xl bg-white p-5 shadow-lg shadow-slate-200 ring-1 ring-slate-100">
                                @foreach ([90, 70, 85, 60, 75] as $width)
                                    <span class="block h-1.5 rounded-full bg-slate-100" style="width: {{ $width }}%"></span>
                                @endforeach
                            </span>
                            <span class="absolute -left-6 top-6 flex h-24 w-24 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-xl shadow-indigo-500/30">
                                <i class="h-11 w-11" data-lucide="{{ $legal['artwork'] }}"></i>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-6xl px-4 py-10 sm:py-14 md:px-6">
            <div class="grid gap-8 {{ $sections !== [] ? 'lg:grid-cols-[280px_1fr]' : '' }}">
                @if ($sections !== [])
                    <aside class="hidden lg:block" data-purpose="page-contents">
                        <nav aria-label="On this page"
                            class="sticky top-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('On this page') }}</h2>
                            <ul class="mt-4 space-y-1">
                                @foreach ($sections as $section)
                                    <li>
                                        <a href="#{{ $section['id'] }}" data-toc-link="{{ $section['id'] }}"
                                            class="flex items-start gap-3 rounded-lg border-s-2 border-transparent px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600">
                                            <i class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" data-lucide="{{ $iconFor($section['title']) }}" aria-hidden="true"></i>
                                            <span>{{ $section['title'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    </aside>
                @endif

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div data-purpose="page-content"
                        class="max-w-none break-words text-[15px] leading-7 text-slate-600
                            [&>h2]:mb-2 [&>h2]:mt-8 [&>h2]:scroll-mt-28 [&>h2]:text-lg [&>h2]:font-bold [&>h2]:tracking-tight [&>h2]:text-slate-950 [&>h2:first-child]:mt-0
                            [&>h2~h2]:border-t [&>h2~h2]:border-slate-100 [&>h2~h2]:pt-8
                            [&>h3]:mb-2 [&>h3]:mt-6 [&>h3]:text-base [&>h3]:font-bold [&>h3]:text-slate-950
                            [&>p]:mb-6
                            [&_a]:font-medium [&_a]:text-indigo-600 [&_a]:underline [&_a:hover]:text-indigo-700
                            [&_strong]:font-semibold [&_strong]:text-slate-900
                            [&_ul]:mb-6 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mb-6 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mb-2
                            [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-xl
                            [&_pre]:overflow-x-auto [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto">
                        {!! $page->contentWithAnchors() !!}
                    </div>
                </article>
            </div>

            <section class="mt-10 rounded-2xl bg-indigo-50/70 p-6 sm:p-8" data-purpose="page-help">
                <div class="grid gap-8 lg:grid-cols-[1.2fr_auto_1fr] lg:items-center">
                    <div class="flex items-center gap-6">
                        <span class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-full bg-indigo-100/70 sm:flex" aria-hidden="true">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm">
                                <i class="h-6 w-6" data-lucide="headphones"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-950">{{ __('Have Questions?') }}</h2>
                            <p class="mt-1.5 max-w-sm text-sm leading-6 text-slate-600">
                                {{ __("If you have any questions about this :page or need further clarification, we're here to help.", ['page' => $page->title]) }}
                            </p>
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
                                    <i class="h-5 w-5" data-lucide="phone" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-950 group-hover:text-indigo-600">{{ __('Call Us') }}</span>
                                    <span class="block truncate text-sm text-slate-600">{{ setting('support_hours', __('Available Mon - Fri, 9AM - 6PM')) }}</span>
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            @if ($legal && $legal['note'])
                <p class="mx-auto mt-8 flex max-w-2xl items-start justify-center gap-2 text-center text-sm text-slate-600">
                    <i class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" data-lucide="shield-check" aria-hidden="true"></i>
                    <span>{{ $legal['note'] }}</span>
                </p>
            @endif
        </div>
    </main>
@endsection

@if ($sections !== [])
    @push('scripts')
        <script data-purpose="page-contents-highlight">
            const tocLinks = document.querySelectorAll('[data-toc-link]');
            const tocHeadings = [...tocLinks].map((link) => document.getElementById(link.dataset.tocLink)).filter(Boolean);
            const tocActiveClasses = ['border-indigo-600', 'bg-indigo-50/60', 'font-semibold', 'text-indigo-600'];

            if (tocHeadings.length) {
                const setActiveSection = (id) => tocLinks.forEach((link) => {
                    link.classList.toggle('border-transparent', link.dataset.tocLink !== id);
                    tocActiveClasses.forEach((cls) => link.classList.toggle(cls, link.dataset.tocLink === id));
                });

                const tocObserver = new IntersectionObserver((entries) => {
                    const visible = entries.filter((entry) => entry.isIntersecting);

                    if (visible.length) {
                        setActiveSection(visible[0].target.id);
                    }
                }, { rootMargin: '-100px 0px -70% 0px' });

                tocHeadings.forEach((heading) => tocObserver.observe(heading));
                setActiveSection(tocHeadings[0].id);
            }
        </script>
    @endpush
@endif
