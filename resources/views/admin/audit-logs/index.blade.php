<x-admin-layout title="Audit Logs">
    @php
        $avatarStyles = [
            'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
            'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        ];

        $hasFilters = request()->hasAny(['admin_id', 'action', 'from', 'to']);
    @endphp

    <x-admin-page-header title="Audit Logs"
        :breadcrumbs="[['label' => 'Audit Logs']]"
        description="Track all administrative actions.">
        <x-slot name="actions">
            <x-admin.export-button resource="audit-logs" />
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.audit-logs.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="action" value="{{ request('action') }}" placeholder="Search actions..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="admin_id" onchange="this.form.submit()"
                    class="max-w-44 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Admins</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" @selected(request('admin_id') == $admin->id)>{{ $admin->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" aria-label="From date"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">

            <input type="date" name="to" value="{{ request('to') }}" aria-label="To date"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">

            <div class="ml-auto flex items-center gap-2">
                @if ($hasFilters)
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                        Clear
                    </a>
                @endif
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'funnel', 'class' => 'w-4 h-4 text-slate-400'])
                    Apply
                </button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Admin</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Action</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Model</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Changes</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">IP</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse($logs as $log)
                        @php
                            $actorName = $log->admin?->name ?? 'System';
                            $avatarStyle = $avatarStyles[crc32($actorName) % count($avatarStyles)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $log->id }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($actorName, 0, 2)) }}
                                    </span>
                                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $actorName }}</span>
                                </div>
                            </td>

                            <td class="px-3 py-3.5">
                                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700 dark:bg-night-800 dark:text-slate-200">{{ $log->action }}</code>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ class_basename($log->model) }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-400 dark:text-night-500">#{{ $log->model_id }}</p>
                            </td>

                            <td class="max-w-40 px-3 py-3.5">
                                @if($log->changes)
                                    <code class="block truncate font-mono text-xs text-slate-400 dark:text-night-500">@json($log->changes)</code>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-night-500">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $log->ip_address ?? '—' }}</td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-500 dark:text-slate-400">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
