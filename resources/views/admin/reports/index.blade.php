<x-admin-layout title="Moderation Reports">
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

    <x-admin-page-header title="Moderation Reports"
        :breadcrumbs="[['label' => 'Reports']]"
        description="Review and act on user-submitted reports." />

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.reports.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reports..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Statuses</option>
                @foreach (['pending', 'actioned', 'dismissed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="type" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Types</option>
                @foreach ($types as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status', 'type', 'search']))
                <a href="{{ route('admin.reports.index') }}"
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
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reporter</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reason</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Date</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($reports as $report)
                        @php
                            $reporterName = $report->reporter?->name ?? '—';
                            $avatarStyle = $avatarStyles[crc32($reporterName) % count($avatarStyles)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400">{{ $report->id }}</td>

                            <td class="min-w-56 max-w-72 px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($reporterName, 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $reporterName }}</p>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'envelope', 'class' => 'w-3.5 h-3.5 shrink-0'])
                                            <span class="truncate">{{ $report->reporter?->email ?? '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300">
                                    @include('admin.partials.icon', ['name' => 'flag', 'class' => 'w-3.5 h-3.5 shrink-0 text-slate-400'])
                                    {{ class_basename($report->reportable_type) }}
                                </span>
                            </td>

                            <td class="max-w-40 truncate px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $report->reason ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($report->status) { 'pending' => 'yellow', 'actioned' => 'blue', 'dismissed' => 'gray', default => 'gray' }">
                                    {{ ucfirst($report->status) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $report->created_at->format('M d, Y') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @can('reports.edit')
                                    @if ($report->status === 'pending')
                                        <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                            <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                                @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                            </button>
                                            <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                                <form method="POST" action="{{ route('admin.reports.action', $report) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="dismiss">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                        @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4 text-slate-400'])
                                                        Dismiss
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.reports.action', $report) }}"
                                                      data-confirm="Warn the user?">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="warn">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10">
                                                        @include('admin.partials.icon', ['name' => 'exclamation-circle', 'class' => 'w-4 h-4'])
                                                        Warn
                                                    </button>
                                                </form>

                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.reports.action', $report) }}"
                                                      data-confirm="Block the user and remove content? This cannot be undone.">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="block">
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                                        Block
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reports->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
