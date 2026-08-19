<x-admin-layout title="Maintenance Requests">
    @php
        $avatarStyles = [
            'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
            'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        ];

        $statuses = ['open', 'assigned', 'in_progress', 'completed', 'closed'];
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $hasFilters = request()->hasAny(['status', 'priority']);
    @endphp

    <x-admin-page-header title="Maintenance Requests"
        :breadcrumbs="[['label' => 'Maintenance Requests']]"
        description="Manage property maintenance requests.">
        <x-slot name="actions">
            <x-admin.export-button resource="maintenance-requests" />
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.maintenance-requests.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>

            <select name="priority" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All priorities</option>
                @foreach ($priorities as $p)
                    <option value="{{ $p }}" @selected(request('priority') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>

            @if ($hasFilters)
                <a href="{{ route('admin.maintenance-requests.index') }}"
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
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listing</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Tenant</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Technician</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Priority</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($maintenanceRequests as $request)
                        @php
                            $avatarStyle = $avatarStyles[crc32($request->tenant?->name ?? '—') % count($avatarStyles)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $request->id }}</td>

                            <td class="max-w-64 px-3 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $request->listing?->title ?? '—' }}</p>
                                    @if ($request->title)
                                        <p class="truncate text-xs text-slate-400 dark:text-night-500">{{ $request->title }}</p>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($request->tenant?->name ?? '—', 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $request->tenant?->name ?? '—' }}</p>
                                        <p class="truncate text-xs text-slate-400 dark:text-night-500">{{ $request->tenant?->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $request->technician?->user?->name ?? '—' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($request->priority) { 'urgent' => 'red', 'high' => 'yellow', 'normal' => 'blue', 'low' => 'gray', default => 'gray' }">
                                    {{ ucfirst($request->priority) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($request->status) { 'open' => 'yellow', 'assigned' => 'blue', 'in_progress' => 'purple', 'completed' => 'green', 'closed' => 'gray', default => 'gray' }">
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @can('technician_bookings.edit')
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            <p class="px-3.5 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Set status</p>
                                            @foreach ($statuses as $s)
                                                @continue($request->status === $s)
                                                <form method="POST" action="{{ route('admin.maintenance-requests.update', $request) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $s }}">
                                                    <input type="hidden" name="priority" value="{{ $request->priority }}">
                                                    @if ($request->technician_id)
                                                        <input type="hidden" name="technician_id" value="{{ $request->technician_id }}">
                                                    @endif
                                                    <button type="submit"
                                                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm {{ $s === 'completed' ? 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60' }}">
                                                        @include('admin.partials.icon', ['name' => $s === 'completed' ? 'check' : 'wrench-screwdriver', 'class' => 'w-4 h-4 '.($s === 'completed' ? '' : 'text-slate-400')])
                                                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-night-500">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No maintenance requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $maintenanceRequests->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
