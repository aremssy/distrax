<x-admin-layout title="Roles">
    <x-admin-page-header title="Roles & Permissions"
        :breadcrumbs="[['label' => 'Roles']]"
        description="Manage admin roles and their assigned permissions.">
        <x-slot name="actions">
            <x-admin.export-button resource="roles" />

            @can('roles.create')
                <a href="{{ route('admin.roles.create') }}"
                   class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Role
                </a>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Role</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Guard</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Users</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Created</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse($roles as $role)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $role->id }}</td>

                            <td class="px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300">
                                        @include('admin.partials.icon', ['name' => 'shield-check', 'class' => 'w-4 h-4'])
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $role->display_name }}</p>
                                        <p class="mt-0.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $role->name }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                                {{ $role->guard_name }}
                            </td>

                            <td class="px-3 py-3.5 text-center">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-night-800 dark:text-slate-300">
                                    {{ $role->users_count }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $role->created_at->format('d M Y') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        @can('roles.edit')
                                            <a href="{{ route('admin.roles.edit', $role) }}"
                                               class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </a>
                                        @endcan
                                        @can('roles.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                  data-confirm="Delete {{ addslashes($role->display_name) }}?">
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
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
