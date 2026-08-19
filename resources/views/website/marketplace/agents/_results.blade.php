@if ($agents->isEmpty())
    <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <i class="mx-auto h-10 w-10 text-slate-500" data-lucide="users" aria-hidden="true"></i>
        <h2 class="mt-4 text-xl font-bold text-slate-950">
            {{ $search !== '' ? __('No agents matched your search') : __('No agents yet') }}
        </h2>
        <p class="mx-auto mt-2 max-w-md text-base text-slate-600">
            {{ $search !== '' ? __('Try a different name, title or agency.') : __('Our agent directory is being prepared. Please check back shortly.') }}
        </p>
        @if ($search !== '')
            <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                href="{{ route('agents.index') }}">{{ __('Clear search') }}</a>
        @endif
    </div>
@else
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($agents as $agent)
            @php $isVerified = $agent->user?->verification_status === 'verified'; @endphp
            <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                data-purpose="agent-card">
                @if ($isVerified)
                    <span class="absolute end-4 top-4 flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-600">
                        <i class="h-3 w-3" data-lucide="badge-check" aria-hidden="true"></i>{{ __('Verified') }}
                    </span>
                @endif

                <div class="flex items-center gap-4 pt-6">
                    <img class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-slate-50"
                        src="{{ $agent->user?->avatar ? asset('storage/'.$agent->user->avatar) : asset('assets/images/website/agent/agent-1.webp') }}"
                        alt="{{ $agent->user?->name }}" loading="lazy" width="80" height="80">
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-slate-950">
                            <a class="line-clamp-1 transition after:absolute after:inset-0 group-hover:text-indigo-600"
                                href="{{ route('agents.show', $agent) }}">{{ $agent->user?->name }}</a>
                        </h2>
                        @if ($agent->title)
                            <p class="mt-1 line-clamp-1 text-sm text-slate-600">{{ $agent->title }}</p>
                        @endif
                        @if ($agent->agency)
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                                <i class="h-3.5 w-3.5 shrink-0 text-slate-500" data-lucide="building-2" aria-hidden="true"></i>
                                <span class="line-clamp-1">{{ $agent->agency->name }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <p class="mt-4 flex items-center gap-1.5 border-t border-slate-100 pt-4 text-sm font-medium text-slate-600">
                    <i class="h-4 w-4 text-slate-500" data-lucide="house" aria-hidden="true"></i>
                    {{ trans_choice(':count Listing|:count Listings', $agent->listings_count, ['count' => number_format($agent->listings_count)]) }}
                </p>

                <div class="relative z-10 mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('agents.show', $agent) }}"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-indigo-200 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                        {{ __('View Profile') }}
                    </a>
                    @if ($agent->phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $agent->phone) }}"
                            aria-label="{{ __('Call :name', ['name' => $agent->user?->name]) }}"
                            class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="h-4 w-4" data-lucide="phone" aria-hidden="true"></i>
                            {{ __('Contact') }}
                        </a>
                    @else
                        <a href="{{ route('agents.show', $agent) }}"
                            class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="h-4 w-4" data-lucide="mail" aria-hidden="true"></i>
                            {{ __('Contact') }}
                        </a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">{{ __('Showing :from to :to of :total :label', ['from' => number_format($agents->firstItem()), 'to' => number_format($agents->lastItem()), 'total' => number_format($agents->total()), 'label' => Str::plural('agent', $agents->total())]) }}</p>
        <div>{{ $agents->onEachSide(1)->links() }}</div>
    </div>
@endif
