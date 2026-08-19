<x-admin-layout title="Reported Listings">
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

    <x-admin-page-header title="Reported Listings"
        :breadcrumbs="[['label' => 'Listings', 'route' => 'admin.listings.index'], ['label' => 'Reports']]"
        description="Review and action user-submitted reports on listings.">
        <x-slot name="actions">
            @if($pendingCount > 0)
                <x-admin-badge color="red">{{ $pendingCount }} pending</x-admin-badge>
            @endif
        </x-slot>
    </x-admin-page-header>

    {{-- Status tabs --}}
    <div class="mb-4 flex flex-wrap items-center gap-1">
        @foreach(['pending' => 'Pending', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed', 'all' => 'All'] as $val => $label)
            <a href="{{ route('admin.listings.reports.index', ['status' => $val]) }}"
               class="{{ $status === $val
                   ? 'bg-brand text-white rounded-lg px-3 py-1.5 text-xs font-semibold'
                   : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-night-800 rounded-lg px-3 py-1.5 text-xs font-semibold' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listing</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reporter</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reason</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Date</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse($reports as $report)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-52 max-w-72 px-5 py-3.5">
                                @if($report->reportable)
                                    <a href="{{ route('admin.listings.show', $report->reportable->id) }}"
                                       class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                        {{ $report->reportable->title ?? '#'.$report->reportable_id }}
                                    </a>
                                    <div class="mt-1">
                                        <x-admin-badge :color="match($report->reportable->type ?? '') {
                                            'rent' => 'blue', 'sale' => 'green', 'hotel' => 'purple', default => 'gray'
                                        }">{{ ucfirst($report->reportable->type ?? '?') }}</x-admin-badge>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Listing deleted</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @php $reporterAvatar = $avatarStyles[crc32($report->reporter?->name ?? '—') % count($avatarStyles)]; @endphp
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $reporterAvatar }}">
                                        {{ strtoupper(substr($report->reporter?->name ?? '—', 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $report->reporter?->name ?? 'Unknown' }}</p>
                                        @if($report->reporter?->email)
                                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                                @include('admin.partials.icon', ['name' => 'envelope', 'class' => 'w-3.5 h-3.5 shrink-0'])
                                                <span class="truncate">{{ $report->reporter?->email }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="max-w-64 px-3 py-3.5">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $report->reason }}</p>
                                @if($report->details)
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $report->details }}</p>
                                @endif
                                @if($report->admin_note)
                                    <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-400">Note: {{ $report->admin_note }}</p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($report->status) {
                                    'pending' => 'yellow', 'actioned' => 'green', 'dismissed' => 'gray', default => 'gray'
                                }">{{ ucfirst($report->status) }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $report->created_at->diffForHumans() }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @if($report->status === 'pending')
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            {{-- Dismiss --}}
                                            <form method="POST" action="{{ route('admin.listings.reports.dismiss', $report) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Dismiss
                                                </button>
                                            </form>

                                            {{-- Warn owner --}}
                                            <form method="POST" action="{{ route('admin.listings.reports.warn', $report) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10">
                                                    @include('admin.partials.icon', ['name' => 'flag', 'class' => 'w-4 h-4'])
                                                    Warn
                                                </button>
                                            </form>

                                            {{-- Archive listing --}}
                                            @if($report->reportable && $report->reportable->status !== 'archived')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.listings.reports.remove', $report) }}"
                                                      data-confirm="Archive this listing and resolve all reports?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Remove
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">
                                        by {{ $report->reviewer?->name ?? 'system' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">
                                No {{ $status !== 'all' ? $status : '' }} reports found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reports->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
