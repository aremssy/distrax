<x-admin-layout title="Zones">
    <x-admin-page-header title="Zone Management"
        :breadcrumbs="[['label' => 'Zones']]"
        description="Build the Country › City › Area hierarchy that powers all listings.">

        <x-slot name="actions">
            {{-- Export --}}
            <x-admin.export-button resource="zones" />

            {{-- Import --}}
            @can('zones.create')
                <button type="button" onclick="openModal('import-modal')"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'arrow-up-tray', 'class' => 'w-4 h-4 text-slate-400'])
                    Import CSV
                </button>

                {{-- Bulk add --}}
                <button type="button" onclick="openModal('bulk-modal')"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'list-bullet', 'class' => 'w-4 h-4 text-slate-400'])
                    Bulk Add
                </button>

                {{-- Add Zone --}}
                <a href="{{ route('admin.zones.create') }}"
                   class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Add Zone
                </a>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Stats row --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach(['country' => ['label' => 'Countries'], 'state' => ['label' => 'States'], 'city' => ['label' => 'Cities'], 'area' => ['label' => 'Areas']] as $t => $meta)
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                    {{ $t === 'country' ? 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400' :
                       ($t === 'state'   ? 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-400' :
                       ($t === 'city'    ? 'bg-violet-50 text-violet-500 dark:bg-violet-500/15 dark:text-violet-400' :
                                          'bg-slate-100 text-slate-500 dark:bg-night-800 dark:text-slate-400')) }}">
                    @include('admin.partials.icon', ['name' => 'map-pin', 'class' => 'w-5 h-5'])
                </span>
                <div>
                    <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $counts[$t] ?? 0 }}</p>
                    <p class="text-xs text-slate-400 dark:text-night-500">{{ $meta['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.zones.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ $search->value() }}" placeholder="Search zones..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="type" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Types</option>
                @foreach(['country', 'state', 'city', 'area'] as $t)
                    <option value="{{ $t }}" @selected($type->value() === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>

            @if($search->isNotEmpty() || $type->isNotEmpty())
                <a href="{{ route('admin.zones.index') }}"
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
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Name</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Coordinates</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Children</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>

                @if($zones)
                    {{-- Flat search results --}}
                    <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                        @forelse($zones as $zone)
                            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                                <td class="px-5 py-3.5">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $zone->name }}</p>
                                    <p class="mt-0.5 font-mono text-xs text-slate-400 dark:text-night-500">
                                        {{ $zone->slug }}@if($zone->parent) <span class="font-sans">· under {{ $zone->parent->name }}</span>@endif
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <x-admin-badge :color="match($zone->type) {
                                        'country' => 'indigo', 'state' => 'blue', 'city' => 'purple', default => 'gray'
                                    }">{{ ucfirst($zone->type) }}</x-admin-badge>
                                </td>

                                <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">
                                    {{ $zone->lat && $zone->lng
                                        ? number_format((float) $zone->lat, 4) . ', ' . number_format((float) $zone->lng, 4)
                                        : '—' }}
                                </td>

                                <td class="px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">—</td>

                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <x-admin-badge :color="$zone->is_active ? 'green' : 'gray'">
                                        {{ $zone->is_active ? 'Active' : 'Inactive' }}
                                    </x-admin-badge>
                                </td>

                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            @can('zones.create')
                                                <a href="{{ route('admin.zones.create', ['parent_id' => $zone->id]) }}"
                                                   class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Add Child Zone
                                                </a>
                                            @endcan
                                            @can('zones.edit')
                                                <a href="{{ route('admin.zones.edit', $zone) }}"
                                                   class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('zones.delete')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}"
                                                      data-confirm="Delete {{ addslashes($zone->name) }}?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No zones match your search.</td>
                            </tr>
                        @endforelse
                    </tbody>

                @else
                    {{-- Tree view --}}
                    <tbody class="divide-y divide-slate-100 dark:divide-night-800" x-data="{ open: {} }">
                        @if($tree->isEmpty())
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">
                                    No zones yet.
                                    @can('zones.create')
                                        <a href="{{ route('admin.zones.create') }}" class="ml-1 font-medium text-brand hover:underline dark:text-indigo-400">Add the first zone.</a>
                                    @endcan
                                </td>
                            </tr>
                        @else
                            @include('admin.zones._tree_rows', [
                                'zones'       => $tree,
                                'depth'       => 0,
                                'jsCondition' => 'true',
                            ])
                        @endif
                    </tbody>
                @endif
            </table>
        </div>

        {{-- Pagination (flat search only) --}}
        @if($zones)
            {{ $zones->onEachSide(1)->links('admin.partials.pagination') }}
        @endif
    </div>

    {{-- ── Bulk Add Modal ──────────────────────────────────────────────────── --}}
    @can('zones.create')
        <x-admin-modal id="bulk-modal" title="Bulk Add Zones" maxWidth="lg">
            <form method="POST" action="{{ route('admin.zones.bulk-store') }}">
                @csrf
                <div class="space-y-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Paste one zone name per line. All zones will be created under the selected parent.
                    </p>

                    <x-admin.form.select name="parent_id" label="Parent Zone" required>
                        <option value="">— Select parent —</option>
                        @foreach($parents->groupBy('type') as $type => $group)
                            <optgroup label="{{ ucfirst($type) }}">
                                @foreach($group as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-admin.form.select>

                    <x-admin.form.select name="type" label="Zone Type" required>
                        <option value="area" selected>Area</option>
                        <option value="city">City</option>
                        <option value="state">State</option>
                    </x-admin.form.select>

                    <x-admin.form.textarea name="names" label="Zone Names (one per line)" rows="8"
                        placeholder="Gulshan&#10;Banani&#10;Dhanmondi&#10;Mirpur" required />
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('bulk-modal')"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-night-700 dark:text-slate-200 dark:hover:bg-night-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        Add Zones
                    </button>
                </div>
            </form>
        </x-admin-modal>

        {{-- ── Import Modal ────────────────────────────────────────────────── --}}
        <x-admin-modal id="import-modal" title="Import Zones from CSV" maxWidth="lg">
            <form method="POST" action="{{ route('admin.zones.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 font-mono text-xs leading-relaxed text-indigo-700 dark:border-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-300">
                        Expected CSV columns:<br>
                        <strong>name, type, parent_slug, lat, lng, is_active</strong><br><br>
                        Example row:<br>
                        Dhaka, city, bangladesh, 23.8103, 90.4125, 1
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            CSV File <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="block w-full cursor-pointer text-sm text-slate-600 dark:text-slate-300
                                      file:mr-3 file:cursor-pointer file:rounded-lg file:border-0
                                      file:bg-indigo-50 file:px-4 file:py-2 file:text-sm
                                      file:font-semibold file:text-indigo-700
                                      hover:file:bg-indigo-100 dark:file:bg-indigo-500/15
                                      dark:file:text-indigo-400 dark:hover:file:bg-indigo-900/50" />
                        @error('csv_file')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('import-modal')"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-night-700 dark:text-slate-200 dark:hover:bg-night-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        Import
                    </button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
