<x-admin-layout title="Agencies">
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

    <x-admin-page-header title="Agencies"
        :breadcrumbs="[['label' => 'Agencies']]"
        description="Manage real estate agencies and their agents.">

        <x-slot name="actions">
            <x-admin.export-button resource="agencies" />

            @can('agencies.create')
                <button type="button" onclick="openModal('agency-import-modal')"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'arrow-up-tray', 'class' => 'w-4 h-4 text-slate-400'])
                    Import CSV
                </button>

                <a href="{{ route('admin.agencies.create') }}"
                   class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    New Agency
                </a>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.agencies.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or phone..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            @if (request()->hasAny(['search']))
                <a href="{{ route('admin.agencies.index') }}"
                   class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
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
                        <th class="w-10 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Agency</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Owner</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Agents</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listings</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse($agencies as $agency)
                        @php
                            $logoUrl = $agency->logo
                                ? (str_starts_with($agency->logo, 'http') ? $agency->logo : asset('storage/'.$agency->logo))
                                : null;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400">{{ $agency->id }}</td>

                            <td class="min-w-52 max-w-72 px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $agency->name }}" loading="lazy"
                                             class="h-11 w-14 shrink-0 rounded-lg object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @else
                                        <div class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('admin.agencies.show', $agency) }}"
                                               class="truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                                {{ $agency->name }}
                                            </a>
                                            @if($agency->is_verified)
                                                <x-admin-badge color="blue">✓ Verified</x-admin-badge>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'envelope', 'class' => 'w-3.5 h-3.5 shrink-0'])
                                            <span class="truncate">{{ $agency->phone ?? '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @php $ownerAvatar = $avatarStyles[crc32($agency->owner?->name ?? '—') % count($avatarStyles)]; @endphp
                                <span class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $ownerAvatar }}">
                                        {{ strtoupper(substr($agency->owner?->name ?? '—', 0, 2)) }}
                                    </span>
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $agency->owner?->name ?? '—' }}</span>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-night-800 dark:text-slate-300">
                                    {{ $agency->agents_count }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-night-800 dark:text-slate-300">
                                    {{ $agency->listings_count }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @php
                                    $sColor = match($agency->status) {
                                        'active' => 'green',
                                        'inactive' => 'gray',
                                        'suspended' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-admin-badge :color="$sColor">{{ ucfirst($agency->status) }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.agencies.show', $agency) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>
                                        @can('agencies.edit')
                                            <a href="{{ route('admin.agencies.edit', $agency) }}"
                                               class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No agencies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $agencies->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('agencies.create')
        {{-- ── Import Modal ────────────────────────────────────────────────── --}}
        <x-admin-modal id="agency-import-modal" title="Import Agencies from CSV" maxWidth="lg">
            <form method="POST" action="{{ route('admin.agencies.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-800 p-3 text-xs text-indigo-700 dark:text-indigo-300 font-mono leading-relaxed">
                        Expected CSV columns:<br>
                        <strong>name, owner_email, zone_slug, phone, email, website, address, is_verified</strong><br><br>
                        Example row:<br>
                        Prime Realty, owner@example.com, dhaka, 01700000000, hello@prime.com, https://prime.com, Gulshan, 1
                        <br><br>
                        Rows without a <strong>name</strong>, or whose <strong>owner_email</strong> does not match an
                        existing user, are skipped.
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            CSV File <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="block w-full text-sm text-slate-600 dark:text-slate-300
                                      file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-500/15
                                      file:text-indigo-700 dark:file:text-indigo-400
                                      hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                                      file:cursor-pointer cursor-pointer" />
                        @error('csv_file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('agency-import-modal')"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        Import
                    </button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
