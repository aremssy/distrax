<x-admin-layout title="Email Templates">
    <x-admin-page-header title="Email & SMS Templates"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Templates']]">
        <x-slot name="actions">
            <x-admin.export-button resource="email-templates" />
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Template</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Channel</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($templates as $tpl)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-night-800 dark:text-slate-300">
                                        @include('admin.partials.icon', ['name' => $tpl->channel === 'email' ? 'envelope' : 'document-text', 'class' => 'w-4 h-4'])
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.email-templates.edit', $tpl) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $tpl->name }}
                                        </a>
                                        <p class="mt-0.5 truncate font-mono text-xs text-slate-400 dark:text-night-500">{{ $tpl->key }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$tpl->channel === 'email' ? 'blue' : 'purple'">{{ strtoupper($tpl->channel) }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$tpl->is_active ? 'green' : 'gray'">{{ $tpl->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.email-templates.edit', $tpl) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                            Edit Template
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-sm text-slate-400">No templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
