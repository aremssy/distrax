@extends('website.layouts.master')
@section('title', ($post->meta_title ?: $post->title).' | '.config('app.name'))

@push('head')
    <meta name="description" content="{{ $post->meta_description ?: Str::limit(strip_tags($post->content), 155) }}">
@endpush

@section('content')
    @php
        $image = match (true) {
            ! $post->featured_image => asset('assets/images/website/features/flat-1.webp'),
            str_starts_with($post->featured_image, 'http') => $post->featured_image,
            default => asset('storage/'.$post->featured_image),
        };
        $postUrl = route('blog.show', $post->slug);
        $shareLinks = [
            ['label' => __('Share on Facebook'), 'icon' => 'facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($postUrl)],
            ['label' => __('Share on X'), 'icon' => 'twitter', 'url' => 'https://twitter.com/intent/tweet?url='.urlencode($postUrl).'&text='.urlencode($post->title)],
            ['label' => __('Share on LinkedIn'), 'icon' => 'linkedin', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url='.urlencode($postUrl)],
        ];
    @endphp

    <main class="min-h-screen bg-slate-50 py-10 sm:py-14" data-purpose="blog-detail-page">
        <article class="mx-auto max-w-3xl px-4 md:px-6">
            <nav aria-label="Breadcrumb" class="mb-8">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <li><a class="text-indigo-600 transition hover:text-indigo-700" href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li><a class="text-indigo-600 transition hover:text-indigo-700" href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                    @if ($post->category)
                        <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                        <li>
                            <a class="text-indigo-600 transition hover:text-indigo-700"
                                href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
                        </li>
                    @endif
                    <li aria-hidden="true"><i class="h-3.5 w-3.5 text-slate-500" data-lucide="chevron-right"></i></li>
                    <li class="line-clamp-1 text-slate-600" aria-current="page">{{ $post->title }}</li>
                </ol>
            </nav>

            @if ($post->category)
                <a class="inline-flex rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 transition hover:bg-indigo-100"
                    href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
            @endif

            <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                {{ $post->title }}
            </h1>

            <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-slate-600">
                <span class="flex items-center gap-2.5">
                    @if ($post->author?->avatar)
                        <img class="h-9 w-9 rounded-full object-cover" src="{{ asset('storage/'.$post->author->avatar) }}"
                            alt="" width="36" height="36">
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600" aria-hidden="true">
                            {{ Str::upper(Str::substr($post->author?->name ?? 'R', 0, 1)) }}
                        </span>
                    @endif
                    <span class="font-bold text-slate-900">{{ $post->author?->name ?? __('Rentdo Team') }}</span>
                </span>
                <span class="hidden h-5 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                @if ($post->published_at)
                    <time class="flex items-center gap-1.5" datetime="{{ $post->published_at->toDateString() }}">
                        <i class="h-4 w-4 text-slate-500" data-lucide="calendar" aria-hidden="true"></i>
                        {{ $post->published_at->format('M j, Y') }}
                    </time>
                    <span class="hidden h-5 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                @endif
                <span class="flex items-center gap-1.5">
                    <i class="h-4 w-4 text-slate-500" data-lucide="eye" aria-hidden="true"></i>
                    {{ __(':count views', ['count' => number_format($post->view_count)]) }}
                </span>
                <span class="hidden h-5 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                <span class="flex items-center gap-1.5">
                    <i class="h-4 w-4 text-slate-500" data-lucide="clock" aria-hidden="true"></i>
                    {{ __(':minutes min read', ['minutes' => $post->readMinutes()]) }}
                </span>
            </div>

            @unless ($lowDataMode ?? false)
                <figure class="mt-8 aspect-video overflow-hidden rounded-2xl bg-slate-100 shadow-sm">
                    <img class="h-full w-full object-cover" src="{{ $image }}" alt="{{ $post->title }}" width="1280" height="720">
                </figure>
            @endunless

            <div data-purpose="post-content"
                class="mt-10 text-[17px] leading-8 text-slate-700
                    [&_p]:mb-6
                    [&_p:first-of-type]:first-letter:float-left [&_p:first-of-type]:first-letter:mr-3 [&_p:first-of-type]:first-letter:mt-1 [&_p:first-of-type]:first-letter:rounded-lg [&_p:first-of-type]:first-letter:bg-indigo-600 [&_p:first-of-type]:first-letter:px-4 [&_p:first-of-type]:first-letter:py-2 [&_p:first-of-type]:first-letter:text-4xl [&_p:first-of-type]:first-letter:font-bold [&_p:first-of-type]:first-letter:leading-tight [&_p:first-of-type]:first-letter:text-white
                    [&_h2]:mb-4 [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-slate-950
                    [&_h3]:mb-3 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-slate-950
                    [&_a]:font-medium [&_a]:text-indigo-600 [&_a]:underline [&_a:hover]:text-indigo-700
                    [&_strong]:font-semibold [&_strong]:text-slate-900
                    [&_ul]:mb-6 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mb-6 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mb-2
                    [&_blockquote]:my-6 [&_blockquote]:border-s-4 [&_blockquote]:border-indigo-500 [&_blockquote]:bg-white [&_blockquote]:px-5 [&_blockquote]:py-3 [&_blockquote]:italic [&_blockquote]:text-slate-600
                    [&_img]:my-6 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-xl
                    [&_pre]:overflow-x-auto [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto
                    lg:[&_table]:table lg:[&_table]:max-w-none lg:[&_table]:overflow-visible">
                {!! $post->content !!}
            </div>

            <div class="mt-12 flex flex-col gap-5 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                @if ($post->tags)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-bold text-slate-900">{{ __('Tags:') }}</span>
                        @foreach ($post->tags as $tag)
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-600">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center gap-2" data-purpose="post-share">
                    <span class="text-sm font-bold text-slate-900">{{ __('Share:') }}</span>
                    @foreach ($shareLinks as $share)
                        <a href="{{ $share['url'] }}" target="_blank" rel="noopener" aria-label="{{ $share['label'] }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-indigo-600 hover:text-white">
                            <x-website.social-icon :platform="$share['icon']" class="h-4 w-4" />
                        </a>
                    @endforeach
                    <button type="button" data-copy-link="{{ $postUrl }}" aria-label="{{ __('Copy link to this post') }}"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-indigo-600 hover:text-white">
                        <i class="h-4 w-4" data-lucide="link" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            @if ($relatedPosts->isNotEmpty())
                <section class="mt-14" data-purpose="related-posts">
                    <h2 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ __('Related posts') }}</h2>
                    <div class="mt-6 grid gap-5">
                        @foreach ($relatedPosts as $related)
                            @php
                                $relatedImage = match (true) {
                                    ! $related->featured_image => asset('assets/images/website/features/flat-1.webp'),
                                    str_starts_with($related->featured_image, 'http') => $related->featured_image,
                                    default => asset('storage/'.$related->featured_image),
                                };
                            @endphp
                            <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:flex-row">
                                <div class="aspect-video w-full shrink-0 overflow-hidden bg-slate-100 sm:aspect-square sm:w-44">
                                    <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        src="{{ $relatedImage }}" alt="{{ $related->title }}" loading="lazy">
                                </div>
                                <div class="flex flex-1 flex-col justify-center gap-2 p-5">
                                    @if ($related->category)
                                        <span class="inline-flex w-fit rounded-md bg-indigo-50 px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-indigo-600">
                                            {{ $related->category->name }}
                                        </span>
                                    @endif
                                    <h3 class="text-lg font-bold leading-snug text-slate-950">
                                        <a class="line-clamp-2 transition after:absolute after:inset-0 group-hover:text-indigo-600"
                                            href="{{ route('blog.show', $related->slug) }}">{{ $related->title }}</a>
                                    </h3>
                                    @if ($related->published_at)
                                        <time class="flex items-center gap-1.5 text-sm text-slate-600" datetime="{{ $related->published_at->toDateString() }}">
                                            <i class="h-3.5 w-3.5 text-slate-500" data-lucide="calendar" aria-hidden="true"></i>
                                            {{ $related->published_at->format('M j, Y') }}
                                        </time>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mt-10 rounded-2xl bg-indigo-50/70 p-6 sm:p-8" data-purpose="newsletter-signup">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm">
                        <i class="h-6 w-6" data-lucide="mail" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-bold tracking-tight text-slate-950">{{ __("Don't Miss Out") }}</h2>
                        <p class="mt-1.5 text-sm text-slate-600">{{ __('Subscribe to our newsletter and get the latest articles and updates in your inbox.') }}</p>
                    </div>
                    <form action="{{ route('newsletter.store') }}" method="POST" class="w-full lg:w-auto">
                        @csrf
                        <input type="hidden" name="source" value="blog">
                        <div class="flex gap-2">
                            <label class="sr-only" for="newsletter-email">{{ __('Email address') }}</label>
                            <input id="newsletter-email" type="email" name="email" required placeholder="{{ __('Enter your email') }}"
                                value="{{ old('email') }}"
                                class="h-11 w-full min-w-0 rounded-lg border border-slate-200 bg-white px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:w-64">
                            <button type="submit"
                                class="h-11 shrink-0 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                {{ __('Subscribe') }}
                            </button>
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </section>
        </article>
    </main>
@endsection

@push('scripts')
    <script data-purpose="copy-post-link">
        document.querySelectorAll('[data-copy-link]').forEach((button) => {
            button.addEventListener('click', async () => {
                await navigator.clipboard?.writeText(button.dataset.copyLink);
                button.classList.add('bg-indigo-600', 'text-white');
                setTimeout(() => button.classList.remove('bg-indigo-600', 'text-white'), 1200);
            });
        });
    </script>
@endpush
