{{--
    Recursive tree row partial.
    Variables:
      $zones        — collection of Zone models (with allChildren eager-loaded)
      $depth        — current nesting depth (0 = root)
      $jsCondition  — Alpine.js expression that controls row visibility
--}}
@foreach($zones as $zone)
    <tr
        x-show="{{ $jsCondition }}"
        x-transition:enter="transition-opacity ease-out duration-75"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40"
    >
        {{-- Name + expand toggle --}}
        <td class="px-5 py-3.5" style="padding-left: {{ 20 + $depth * 24 }}px">
            <div class="flex items-center gap-1.5">
                @if($zone->allChildren->isNotEmpty())
                    <button type="button"
                        @click.stop="open[{{ $zone->id }}] = !open[{{ $zone->id }}]"
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-slate-400 transition-transform duration-150 hover:text-brand dark:hover:text-indigo-400"
                        :class="{ 'rotate-90': open[{{ $zone->id }}] }">
                        @include('admin.partials.icon', ['name' => 'chevron-right', 'class' => 'w-3.5 h-3.5'])
                    </button>
                @else
                    <span class="w-5 shrink-0"></span>
                @endif

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $zone->name }}</p>
                    <p class="mt-0.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $zone->slug }}</p>
                </div>
            </div>
        </td>

        {{-- Type badge --}}
        <td class="whitespace-nowrap px-3 py-3.5">
            <x-admin-badge :color="match($zone->type) {
                'country' => 'indigo',
                'state'   => 'blue',
                'city'    => 'purple',
                default   => 'gray'
            }">{{ ucfirst($zone->type) }}</x-admin-badge>
        </td>

        {{-- Coordinates --}}
        <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">
            {{ $zone->lat && $zone->lng
                ? number_format((float) $zone->lat, 4) . ', ' . number_format((float) $zone->lng, 4)
                : '—' }}
        </td>

        {{-- Child count --}}
        <td class="px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $zone->allChildren->count() ?: '—' }}
        </td>

        {{-- Status --}}
        <td class="whitespace-nowrap px-3 py-3.5">
            <x-admin-badge :color="$zone->is_active ? 'green' : 'gray'">
                {{ $zone->is_active ? 'Active' : 'Inactive' }}
            </x-admin-badge>
        </td>

        {{-- Actions --}}
        <td class="whitespace-nowrap px-5 py-3.5 text-right">
            <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                <button type="button" @click.stop="open = !open"
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
                        @unless($zone->allChildren->isNotEmpty())
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
                        @endunless
                    @endcan
                </div>
            </div>
        </td>
    </tr>

    @if($zone->allChildren->isNotEmpty())
        @include('admin.zones._tree_rows', [
            'zones'       => $zone->allChildren,
            'depth'       => $depth + 1,
            'jsCondition' => $jsCondition . ' && open[' . $zone->id . ']',
        ])
    @endif
@endforeach
