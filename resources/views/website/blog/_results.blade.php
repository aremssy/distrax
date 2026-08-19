@php
    $featuredPost = $showFeatured ? $posts->first() : null;
    $gridPosts = $featuredPost ? $posts->getCollection()->slice(1) : $posts->getCollection();
@endphp
@if ($posts->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500" data-purpose="blog-empty-state">
        @if (request('q'))
            {{ __('No articles found for ":query".', ['query' => request('q')]) }}
        @else
            {{ __('No articles found.') }}
        @endif
        <a href="{{ route('blog.index') }}" class="font-semibold text-indigo-600 hover:underline">{{ __('Browse all articles') }}</a>
    </div>
@else
    {{-- Featured article --}}
    @if ($featuredPost)
        <article class="group mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-lg" data-purpose="featured-post">
            <a href="{{ route('blog.show', $featuredPost->slug) }}" class="grid grid-cols-1 md:grid-cols-2">
                <div class="aspect-[4/3] overflow-hidden bg-slate-100 md:aspect-auto md:min-h-80">
                    <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $featuredPost->featuredImageUrl() }}" alt="{{ $featuredPost->title }}">
                </div>
                <div class="flex flex-col justify-center gap-4 p-6 md:p-8">
                    <div class="flex items-center gap-3 text-xs font-semibold">
                        @if ($featuredPost->category)<span class="rounded bg-indigo-50 px-2 py-1 uppercase tracking-wide text-indigo-600">{{ $featuredPost->category->name }}</span>@endif
                        <span class="flex items-center gap-1 text-slate-500"><i class="h-3 w-3" data-lucide="calendar"></i>{{ $featuredPost->published_at?->format('M j, Y') }}</span>
                    </div>
                    <h2 class="text-2xl font-bold leading-tight text-slate-950 group-hover:text-indigo-600 md:text-3xl">{{ $featuredPost->title }}</h2>
                    @if ($featuredPost->excerpt)<p class="line-clamp-3 text-sm leading-relaxed text-slate-500">{{ $featuredPost->excerpt }}</p>@endif
                    <div class="mt-2 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2.5">
                            @if ($featuredPost->author?->avatar)
                                <img class="h-9 w-9 rounded-full object-cover" src="{{ asset('storage/'.$featuredPost->author->avatar) }}" alt="{{ $featuredPost->author->name }}">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">{{ strtoupper(substr($featuredPost->author?->name ?? 'A', 0, 1)) }}</span>
                            @endif
                            <div class="text-xs">
                                <p class="font-semibold text-slate-900">{{ $featuredPost->author?->name }}</p>
                                <p class="text-slate-500">{{ __(':minutes min read', ['minutes' => $featuredPost->readMinutes()]) }}</p>
                            </div>
                        </div>
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-indigo-600">{{ __('Read Full Article') }} <i class="h-4 w-4" data-lucide="arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </article>
    @endif

    {{-- Article grid --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3" data-purpose="blog-grid">
        @foreach ($gridPosts as $post)
            <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-1 hover:shadow-lg">
                <a href="{{ route('blog.show', $post->slug) }}" class="flex h-full flex-col">
                    <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                        <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" loading="lazy">
                    </div>
                    <div class="flex grow flex-col gap-2 p-5">
                        @if ($post->category)<span class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $post->category->name }}</span>@endif
                        <h3 class="line-clamp-2 font-bold text-slate-950 group-hover:text-indigo-600">{{ $post->title }}</h3>
                        @if ($post->excerpt)<p class="line-clamp-2 text-sm text-slate-500">{{ $post->excerpt }}</p>@endif
                        <div class="mt-auto flex items-center gap-2 pt-3 text-xs text-slate-500">
                            @if ($post->author?->avatar)
                                <img class="h-6 w-6 rounded-full object-cover" src="{{ asset('storage/'.$post->author->avatar) }}" alt="{{ $post->author->name }}">
                            @else
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600">{{ strtoupper(substr($post->author?->name ?? 'A', 0, 1)) }}</span>
                            @endif
                            <span class="font-medium text-slate-700">{{ $post->author?->name }}</span>
                            <span class="ms-auto">{{ __(':minutes min read', ['minutes' => $post->readMinutes()]) }}</span>
                        </div>
                    </div>
                </a>
            </article>
        @endforeach
    </div>

    {{-- Load more --}}
    @if ($posts->hasMorePages())
        <div class="mt-12 flex flex-col items-center gap-3" data-purpose="load-more">
            <a href="{{ $posts->nextPageUrl() }}" class="group inline-flex items-center gap-2.5 rounded-full bg-indigo-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-600/25">
                {{ __('Load More Articles') }}
                <i class="h-4 w-4 transition group-hover:translate-x-0.5" data-lucide="arrow-right"></i>
            </a>
            <span class="text-xs text-slate-400">{{ __('Showing :from of :total articles', ['from' => $posts->lastItem(), 'total' => $posts->total()]) }}</span>
        </div>
    @elseif ($posts->currentPage() > 1)
        <div class="mt-12 flex flex-col items-center gap-3" data-purpose="load-more">
            <a href="{{ route('blog.index', request()->except('page')) }}" class="group inline-flex items-center gap-2.5 rounded-full border border-slate-200 bg-white px-8 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md">
                <i class="h-4 w-4 transition group-hover:-translate-x-0.5" data-lucide="arrow-left"></i>
                {{ __('Back to First Page') }}
            </a>
            <span class="text-xs text-slate-400">{{ __("You've reached the end of the list") }}</span>
        </div>
    @endif
@endif
