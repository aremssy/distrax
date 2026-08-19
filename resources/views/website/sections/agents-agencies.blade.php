@if (($topAgents ?? collect())->isNotEmpty() || ($topAgencies ?? collect())->isNotEmpty())
    <section class="py-20" data-purpose="agents-agencies" data-reveal>
        <div class="max-w-7xl mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <div>
                    <div class="mb-8 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl" data-reveal-item>{{ __('Top Rated Agents') }}</h3>
                        <a class="text-indigo-600 text-sm font-semibold flex items-center gap-1" href="{{ route('agents.index') }}">{{ __('View All Agents') }} <i
                                class="w-4 h-4" data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-reveal-children>
                        @forelse ($topAgents ?? [] as $agent)
                            <a href="{{ route('agents.show', $agent) }}" class="bg-white p-4 rounded-xl border border-slate-100 text-center hover:shadow-md transition">
                                <img loading="lazy" decoding="async" width="64" height="64" alt="{{ $agent->user->name }}" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover"
                                    src="{{ $agent->user->avatar ? asset('storage/'.$agent->user->avatar) : asset('assets/images/website/agent/agent-1.webp') }}">
                                <h5 class="text-sm font-bold text-slate-900">{{ $agent->user->name }}</h5>
                                <p class="mb-2 text-xs text-slate-600">{{ $agent->listings_count }} {{ \Illuminate\Support\Str::plural('Property', $agent->listings_count) }}</p>
                            </a>
                        @empty
                            <p class="col-span-full text-base text-slate-600">{{ __('No agents yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-8 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl" data-reveal-item>{{ __('Top Agencies') }}</h3>
                        <a class="text-indigo-600 text-sm font-semibold flex items-center gap-1" href="{{ route('agencies.index') }}">{{ __('View All Agencies') }} <i
                                class="w-4 h-4" data-lucide="arrow-right"></i></a>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-reveal-children>
                        @forelse ($topAgencies ?? [] as $agency)
                            <a href="{{ route('agencies.show', $agency->slug) }}" class="bg-white p-4 rounded-xl border border-slate-100 text-center hover:shadow-md transition">
                                <div class="w-20 h-20 rounded-2xl overflow-hidden mx-auto mb-3 border border-slate-100 bg-white shadow-sm p-2 flex items-center justify-center">
                                    <img loading="lazy" decoding="async" width="80" height="80" src="{{ $agency->logo
                                        ? asset('storage/'.$agency->logo)
                                        : asset('assets/favicon.svg') }}"
                                        alt="{{ $agency->name }} logo" class="h-full w-full object-contain">
                                </div>
                                <h5 class="text-sm font-bold text-slate-900">{{ $agency->name }}</h5>
                                <p class="text-xs text-slate-600">{{ $agency->listings_count }} {{ \Illuminate\Support\Str::plural('Property', $agency->listings_count) }}</p>
                            </a>
                        @empty
                            <p class="col-span-full text-base text-slate-600">{{ __('No agencies yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
