@if ($agencies->isEmpty())
    <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <i class="mx-auto h-10 w-10 text-slate-500" data-lucide="building-2" aria-hidden="true"></i>
        <h2 class="mt-4 text-xl font-bold text-slate-950">
            {{ $search !== '' || $verifiedOnly ? __('No agencies matched your search') : __('No agencies yet') }}
        </h2>
        <p class="mx-auto mt-2 max-w-md text-base text-slate-600">
            {{ $search !== '' || $verifiedOnly ? __('Try a different name or location, or turn off the verified filter.') : __('Our agency directory is being prepared. Please check back shortly.') }}
        </p>
        @if ($search !== '' || $verifiedOnly)
            <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                href="{{ route('agencies.index') }}">{{ __('Clear filters') }}</a>
        @endif
    </div>
@else
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($agencies as $agency)
            <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                data-purpose="agency-card">
                <div class="flex items-start gap-4">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-100 bg-white p-2 shadow-sm">
                        <img class="h-full w-full object-contain"
                            src="{{ $agency->logo ? asset('storage/'.$agency->logo) : asset('assets/favicon.svg') }}"
                            alt="{{ $agency->name }} logo" loading="lazy" width="64" height="64">
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-base font-bold text-slate-950">
                                <a class="line-clamp-1 transition after:absolute after:inset-0 group-hover:text-indigo-600"
                                    href="{{ route('agencies.show', $agency->slug) }}">{{ $agency->name }}</a>
                            </h2>
                            @if ($agency->is_verified)
                                <span class="flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-600">
                                    <i class="h-3 w-3" data-lucide="badge-check" aria-hidden="true"></i>{{ __('Verified') }}
                                </span>
                            @endif
                        </div>

                        @if ($agency->zone || $agency->address)
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-600">
                                <i class="h-3.5 w-3.5 shrink-0 text-slate-500" data-lucide="map-pin" aria-hidden="true"></i>
                                <span class="line-clamp-1">{{ $agency->zone?->name ?? $agency->address }}</span>
                            </p>
                        @endif

                        @if ($agency->reviews_count > 0)
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-600">
                                <i class="h-3.5 w-3.5 shrink-0 fill-current text-amber-400" data-lucide="star" aria-hidden="true"></i>
                                <span class="font-semibold text-slate-900">{{ number_format((float) $agency->reviews_avg_rating, 2) }}</span>
                                <span class="text-slate-500">({{ trans_choice(':count review|:count reviews', $agency->reviews_count, ['count' => $agency->reviews_count]) }})</span>
                            </p>
                        @endif
                    </div>
                </div>

                @if ($agency->description)
                    <p class="mt-4 line-clamp-2 text-sm leading-6 text-slate-600">{{ $agency->description }}</p>
                @endif

                <div class="mt-auto grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-600">
                        <i class="h-4 w-4 text-slate-500" data-lucide="house" aria-hidden="true"></i>
                        {{ trans_choice(':count Property|:count Properties', $agency->listings_count, ['count' => number_format($agency->listings_count)]) }}
                    </span>
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-600">
                        <i class="h-4 w-4 text-slate-500" data-lucide="users" aria-hidden="true"></i>
                        {{ trans_choice(':count Agent|:count Agents', $agency->agents_count, ['count' => number_format($agency->agents_count)]) }}
                    </span>
                </div>

                <a href="{{ route('agencies.show', $agency->slug) }}"
                    class="relative z-10 mt-4 inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-indigo-200 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                    {{ __('View Agency') }} <i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @endforeach
    </div>

    <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">{{ __('Showing :from to :to of :total :label', ['from' => number_format($agencies->firstItem()), 'to' => number_format($agencies->lastItem()), 'total' => number_format($agencies->total()), 'label' => Str::plural('agency', $agencies->total())]) }}</p>
        <div>{{ $agencies->onEachSide(1)->links() }}</div>
    </div>
@endif
