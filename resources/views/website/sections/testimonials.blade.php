@if (($testimonials ?? collect())->isNotEmpty())
    <section class="py-20" data-purpose="testimonials" data-reveal>
        <div class="max-w-7xl mx-auto px-4 md:px-6">
            <div class="text-center mb-14" data-reveal-item>
                <h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('What Our Clients Say') }}</h2>
                <div class="w-16 h-1 bg-indigo-600 rounded-full mx-auto mt-4"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 auto-rows-fr gap-6" data-reveal-children>
                @foreach ($testimonials as $testimonial)
                    <div class="flex h-full flex-col bg-white rounded-3xl border border-slate-100 shadow-sm p-7 hover:shadow-lg transition duration-300">
                        <p class="flex-1 text-slate-600 text-[15px] leading-7 mb-8">
                            &ldquo;{{ $testimonial->quote }}&rdquo;
                        </p>

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <img loading="lazy" decoding="async" width="44" height="44" src="{{ $testimonial->avatar ? asset('storage/'.$testimonial->avatar) : asset('assets/images/website/agent/agent-1.webp') }}"
                                    alt="{{ $testimonial->name }}"
                                    class="w-11 h-11 shrink-0 rounded-full object-cover border border-slate-200" />
                                <div class="min-w-0">
                                    <h5 class="text-[15px] font-semibold text-slate-900">{{ $testimonial->name }}</h5>
                                    <p class="text-xs text-slate-600">{{ $testimonial->role }}</p>
                                </div>
                            </div>

                            <div class="flex shrink-0 text-yellow-400 gap-0.5">
                                @for ($i = 0; $i < $testimonial->rating; $i++)
                                    <i class="w-4 h-4 fill-current" data-lucide="star"></i>
                                @endfor
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
