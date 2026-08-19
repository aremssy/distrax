<x-admin-layout title="Review Moderation">
    @php
        $avatarStyles = [
            'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
            'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        ];
    @endphp

    <x-admin-page-header title="Review Moderation"
        :breadcrumbs="[['label' => 'Reviews']]"
        description="Moderate user reviews across the platform.">
        <x-slot name="actions">
            <x-admin.export-button resource="reviews" />
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.reviews.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reviews..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="visible" value="1" @checked(request()->boolean('visible'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Visible only
            </label>

            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="verified" value="1" @checked(request()->boolean('verified'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Verified only
            </label>

            <button type="submit"
                    class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                @include('admin.partials.icon', ['name' => 'funnel', 'class' => 'w-4 h-4'])
                Apply
            </button>

            @if (request()->hasAny(['visible', 'verified', 'search']))
                <a href="{{ route('admin.reviews.index') }}"
                   class="ml-auto flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reviewer</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reviewable</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Rating</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Comment</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($reviews as $review)
                        @php
                            $reviewerName = $review->reviewer?->name ?? '—';
                            $avatarStyle = $avatarStyles[crc32($reviewerName) % count($avatarStyles)];
                            $rating = (int) $review->rating;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400">{{ $review->id }}</td>

                            <td class="min-w-56 max-w-72 px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($reviewerName, 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $reviewerName }}</p>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'envelope', 'class' => 'w-3.5 h-3.5 shrink-0'])
                                            <span class="truncate">{{ $review->reviewer?->email ?? '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ class_basename($review->reviewable_type) }} #{{ $review->reviewable_id }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="flex items-center gap-1 text-sm text-slate-600 dark:text-slate-300">
                                    @include('admin.partials.icon', ['name' => 'star', 'class' => 'w-3.5 h-3.5 text-amber-500'])
                                    {{ $rating }}<span class="text-xs text-slate-400 dark:text-night-500">/5</span>
                                </span>
                            </td>

                            <td class="max-w-40 truncate px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $review->comment ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <div class="flex items-center gap-1">
                                    <x-admin-badge :color="$review->is_visible ? 'green' : 'gray'">{{ $review->is_visible ? 'Visible' : 'Hidden' }}</x-admin-badge>
                                    @if ($review->is_verified)
                                        <x-admin-badge color="blue">Verified</x-admin-badge>
                                    @endif
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @can('reviews.edit')
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            @if (! $review->is_verified)
                                                <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-sky-600 hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-500/10">
                                                        @include('admin.partials.icon', ['name' => 'shield-check', 'class' => 'w-4 h-4'])
                                                        Verify
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($review->is_visible)
                                                <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="hide">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10">
                                                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                                        Hide
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="show">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                        @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                                                        Show
                                                    </button>
                                                </form>
                                            @endif

                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}"
                                                  data-confirm="Permanently delete this review?">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                    @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No reviews found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reviews->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
