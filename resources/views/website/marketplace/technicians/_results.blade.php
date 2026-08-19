@if ($technicians->isEmpty())
    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <i class="mx-auto h-10 w-10 text-slate-500" data-lucide="wrench" aria-hidden="true"></i>
        <h2 class="mt-4 text-xl font-bold text-slate-950">
            {{ $hasFilters ? __('No technicians matched your search') : __('No technicians yet') }}
        </h2>
        <p class="mx-auto mt-2 max-w-md text-base text-slate-600">
            {{ $hasFilters ? __('Try a different category or area to widen your search.') : __('Our technician directory is being prepared. Please check back shortly.') }}
        </p>
        @if ($hasFilters)
            <a class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                href="{{ route('technicians.index') }}">{{ __('Clear filters') }}</a>
        @endif
    </div>
@else
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($technicians as $technician)
            @php
                $name = $technician->user?->name ?? __('Technician');
                $tone = ['bg-indigo-50 text-indigo-600', 'bg-emerald-50 text-emerald-600', 'bg-amber-50 text-amber-600', 'bg-rose-50 text-rose-600', 'bg-sky-50 text-sky-600', 'bg-violet-50 text-violet-600'][crc32($name) % 6];
            @endphp
            <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                data-purpose="technician-card">
                <div class="flex items-center gap-4">
                    @if ($technician->user?->avatar)
                        <img class="h-11 w-11 shrink-0 rounded-full object-cover"
                            src="{{ asset('storage/'.$technician->user->avatar) }}"
                            alt="{{ $name }}" loading="lazy" width="44" height="44">
                    @else
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-bold {{ $tone }}"
                            aria-hidden="true">{{ str($name)->substr(0, 1)->upper() }}</span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold text-slate-950">
                            <a class="line-clamp-1 transition after:absolute after:inset-0 group-hover:text-indigo-600"
                                href="{{ route('technicians.show', $technician) }}">{{ $name }}</a>
                        </h2>
                        <p class="mt-1 line-clamp-1 text-sm text-slate-600">
                            {{ $technician->zone?->name ? $technician->category?->name.' · '.$technician->zone->name : $technician->category?->name }}
                        </p>
                    </div>

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition group-hover:border-indigo-600 group-hover:bg-indigo-600 group-hover:text-white"
                        aria-hidden="true">
                        <i class="h-4 w-4 rtl:rotate-180" data-lucide="arrow-right"></i>
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-slate-100 pt-4 text-sm">
                    <span class="flex items-center gap-1.5 font-semibold text-slate-800">
                        <i class="h-4 w-4 fill-current text-amber-400" data-lucide="star" aria-hidden="true"></i>
                        <span class="sr-only">{{ __('Rating') }}</span>{{ number_format($technician->rating, 1) }}
                    </span>
                    <span class="h-4 w-px bg-slate-200" aria-hidden="true"></span>
                    <span class="font-medium text-slate-600">{{ trans_choice(':count year|:count years', $technician->experience_years, ['count' => $technician->experience_years]) }}</span>
                    @if ($technician->hourly_rate)
                        <span class="ms-auto font-bold text-slate-950">{{ money($technician->hourly_rate) }}<span class="font-medium text-slate-600">{{ __('/hr') }}</span></span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">{{ __('Showing :first to :last of :total :label', ['first' => number_format($technicians->firstItem()), 'last' => number_format($technicians->lastItem()), 'total' => number_format($technicians->total()), 'label' => Str::plural('technician', $technicians->total())]) }}</p>
        <div>{{ $technicians->onEachSide(1)->links() }}</div>
    </div>
@endif
