@if ($latestPosts->isNotEmpty())
    <section class="py-20 max-w-7xl mx-auto px-4 md:px-6" data-purpose="blog" data-reveal>
        <div class="flex flex-col md:flex-row items-center justify-between mb-10" data-reveal-item>
            <div>
                <h2 class="mb-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('Latest from Blog') }}</h2>
                <div class="w-16 h-1 bg-indigo-600 rounded"></div>
            </div>
            <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">{{ __('View all posts') }}</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" data-reveal-children>
            @foreach ($latestPosts as $post)
                @php
                    $image = match (true) {
                        ! $post->featured_image => asset('assets/images/website/features/flat-1.webp'),
                        str_starts_with($post->featured_image, 'http') => $post->featured_image,
                        default => asset('storage/'.$post->featured_image),
                    };
                @endphp
                <a href="{{ route('blog.show', $post->slug) }}" class="group cursor-pointer">
                    <div class="relative rounded-2xl overflow-hidden aspect-[4/3] mb-4">
                        <img loading="lazy" decoding="async" width="640" height="480" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition"
                            src="{{ $image }}">
                    </div>
                    <h4 class="mb-3 line-clamp-2 text-lg font-bold text-slate-950 transition group-hover:text-indigo-600">
                        {{ $post->title }}</h4>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600">
                        <span class="flex items-center gap-1"><i class="w-3 h-3" data-lucide="calendar"></i> {{ $post->published_at?->format('M j, Y') }}</span>
                        @if ($post->category)<span class="flex items-center gap-1"><i class="w-3 h-3" data-lucide="tag"></i> {{ $post->category->name }}</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
