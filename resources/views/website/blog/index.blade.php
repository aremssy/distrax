@extends('website.layouts.master')
@section('title', __('Blog').' | '.config('app.name'))
@section('content')
    <main class="min-h-screen bg-white py-10 md:py-14">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            {{-- Category filter pills --}}
            <div class="mb-8 flex flex-wrap items-center gap-2" data-purpose="category-filter">
                <a href="{{ route('blog.index') }}" class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ request('category') ? 'border border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:text-indigo-600' : 'bg-indigo-600 text-white' }}">{{ __('All') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ request('category') === $category->slug ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:text-indigo-600' }}">{{ $category->name }}</a>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                {{-- Main content --}}
                <div id="blog-results" class="transition-opacity duration-200 lg:col-span-8 xl:col-span-9">
                    @include('website.blog._results')
                </div>

                {{-- Sidebar --}}
                <aside class="flex flex-col gap-6 lg:col-span-4 xl:col-span-3" data-purpose="blog-sidebar">
                    {{-- Search --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Search') }}</h4>
                        <form action="{{ route('blog.index') }}" method="GET" data-purpose="blog-search" data-live-search="#blog-results">
                            @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                            <label class="relative block">
                                <i class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" data-lucide="search"></i>
                                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Find article...') }}" autocomplete="off" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pe-9 ps-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                <span data-search-spinner class="absolute end-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600" aria-hidden="true"></span>
                            </label>
                        </form>
                    </div>

                    {{-- Popular posts --}}
                    @if ($popularPosts->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-5" data-purpose="popular-posts">
                            <h4 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Popular Posts') }}</h4>
                            <div class="flex flex-col gap-4">
                                @foreach ($popularPosts as $popular)
                                    <a href="{{ route('blog.show', $popular->slug) }}" class="group block">
                                        @if ($popular->category)<span class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">{{ $popular->category->name }}</span>@endif
                                        <p class="line-clamp-2 text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $popular->title }}</p>
                                    </a>
                                    @unless ($loop->last)<hr class="border-slate-100">@endunless
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Categories --}}
                    @if ($categories->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-5" data-purpose="category-list">
                            <h4 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Categories') }}</h4>
                            <ul class="flex flex-col gap-1">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm transition hover:bg-indigo-50 {{ request('category') === $category->slug ? 'bg-indigo-50 font-semibold text-indigo-600' : 'text-slate-600' }}">
                                            <span>{{ $category->name }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $category->blogs_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Newsletter --}}
                    <div class="rounded-2xl bg-indigo-600 p-6 text-white" data-purpose="newsletter-signup">
                        <span class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-white/15"><i class="h-5 w-5" data-lucide="mail"></i></span>
                        <h4 class="text-lg font-bold">{{ __('Weekly Deep Dives') }}</h4>
                        <p class="mt-1 text-sm text-indigo-100">{{ __('Get the latest market insights and property tips delivered directly to your inbox.') }}</p>
                        <form action="{{ route('newsletter.store') }}" method="POST" class="mt-4 flex flex-col gap-2">
                            @csrf
                            <input type="hidden" name="source" value="blog">
                            <input type="email" name="email" required placeholder="email@address.com" class="w-full rounded-lg border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white placeholder:text-indigo-200 focus:border-white/50 focus:outline-none">
                            <button type="submit" class="rounded-lg bg-white px-3 py-2.5 text-sm font-bold text-indigo-600 transition hover:bg-indigo-50">{{ __('Sign Me Up') }}</button>
                        </form>
                        <p class="mt-3 text-center text-[11px] text-indigo-200">{{ __('No spam. Only high-signal content.') }}</p>
                    </div>

                    {{-- Popular tags --}}
                    @if ($popularTags->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-5" data-purpose="popular-tags">
                            <h4 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Popular Tags') }}</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($popularTags as $tag)
                                    <a href="{{ route('blog.index', ['tag' => $tag]) }}" class="rounded-md px-2.5 py-1 text-xs font-semibold transition {{ request('tag') === $tag ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600' }}">#{{ $tag }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>

        {{-- Bottom newsletter CTA --}}
        <section class="mt-16 border-t border-slate-100 bg-white py-16" data-purpose="newsletter-cta">
            <div class="mx-auto max-w-xl px-4 text-center md:px-6">
                <h2 class="text-2xl font-bold tracking-tight text-slate-950 md:text-3xl">{{ __('Stay Ahead of the Curve') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ __('Join thousands of readers who receive our monthly property and market editorial.') }}</p>
                <form action="{{ route('newsletter.store') }}" method="POST" class="mx-auto mt-8 max-w-md">
                    @csrf
                    <input type="hidden" name="source" value="blog">
                    <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1.5 shadow-sm transition focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100">
                        <input type="email" name="email" required placeholder="{{ __('Enter your email') }}" class="w-full min-w-0 grow bg-transparent px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none">
                        <button type="submit" class="shrink-0 whitespace-nowrap rounded-full bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Subscribe Now') }}</button>
                    </div>
                </form>
                <p class="mt-4 text-xs text-slate-400">{{ __('By subscribing, you agree to our Terms of Service and Privacy Policy.') }}</p>
            </div>
        </section>
    </main>
@endsection

