<x-admin-layout title="Languages">
    <x-admin-page-header title="Languages"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Languages']]">
        <x-slot name="actions">
            <x-admin.export-button resource="languages" />

            <a href="{{ route('admin.languages.create') }}"
               class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                Add Language
            </a>
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Language</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Code</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Direction</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Default</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($languages as $lang)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-600 dark:bg-night-800 dark:text-slate-300">
                                        {{ strtoupper(substr($lang->code, 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.languages.edit', $lang) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $lang->name }}
                                        </a>
                                        <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $lang->native_name }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $lang->code }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$lang->direction === 'rtl' ? 'blue' : 'gray'">{{ strtoupper($lang->direction) }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @if ($lang->is_default)
                                    <x-admin-badge color="indigo">Default</x-admin-badge>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-night-500">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$lang->is_active ? 'green' : 'gray'">{{ $lang->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.languages.edit', $lang) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                            Edit
                                        </a>

                                        @unless ($lang->is_default)
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.languages.destroy', $lang) }}"
                                                  data-confirm="Delete this language?">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                    @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                    Delete
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No languages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
