<x-admin-layout title="Technician Categories">
    <x-admin-page-header title="Technician Categories"
        :breadcrumbs="[['label' => 'Technician Categories']]"
        description="Manage technician service categories.">
        <x-slot name="actions">
            <x-admin.export-button resource="technician-categories" />
            @can('technicians.create')
                <button onclick="openModal('create-category-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        @if ($categories->total() > 1)
            <div class="flex justify-end border-b border-slate-100 px-5 py-3 dark:border-night-800">@include('admin.partials.reorder-hint')</div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 px-3 py-3.5"></th>
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Name</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Icon</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Commission</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody data-sortable data-sortable-url="{{ route('admin.technician-categories.reorder') }}" data-sortable-offset="{{ ($categories->currentPage() - 1) * $categories->perPage() }}" class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($categories as $category)
                        <tr data-id="{{ $category->id }}" class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-3 py-3.5 align-middle">@include('admin.partials.drag-handle')</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400">{{ $category->id }}</td>

                            <td class="min-w-48 px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400">
                                        @include('admin.partials.icon', ['name' => 'wrench-screwdriver', 'class' => 'w-4 h-4'])
                                    </span>
                                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $category->name }}</span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-xs text-slate-400 dark:text-night-500">{{ $category->icon ?? '—' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $category->commission_rate }}%</span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$category->is_active ? 'green' : 'gray'">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
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
                                        @can('technicians.edit')
                                            <button type="button" onclick="openModal('edit-category-{{ $category->id }}')"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </button>
                                        @endcan

                                        @can('technicians.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.technician-categories.destroy', $category) }}"
                                                  data-confirm="Delete this category?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
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
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Edit modals --}}
    @can('technicians.edit')
        @foreach ($categories as $category)
            <x-admin-modal id="edit-category-{{ $category->id }}" title="Edit Category">
                <form method="POST" action="{{ route('admin.technician-categories.update', $category) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <x-admin.form.input name="name" label="Name" :value="old('name', $category->name)" required />
                    <x-admin.form.input name="icon" label="Icon" :value="old('icon', $category->icon)" hint="FontAwesome or custom icon class" />
                    <x-admin.form.input name="commission_rate" label="Commission Rate (%)" type="number" :value="old('commission_rate', $category->commission_rate)" required />
                    <x-admin.form.select name="is_active" label="Status">
                        <option value="1" @selected(old('is_active', $category->is_active))>Active</option>
                        <option value="0" @selected(!old('is_active', $category->is_active))>Inactive</option>
                    </x-admin.form.select>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('edit-category-{{ $category->id }}')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
                    </div>
                </form>
            </x-admin-modal>
        @endforeach
    @endcan

    @can('technicians.create')
        <x-admin-modal id="create-category-modal" title="Create Category">
            <form method="POST" action="{{ route('admin.technician-categories.store') }}" class="space-y-4">
                @csrf
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.input name="icon" label="Icon" :value="old('icon')" hint="FontAwesome or custom icon class" />
                <x-admin.form.input name="commission_rate" label="Commission Rate (%)" type="number" :value="old('commission_rate', 0)" required />
                <x-admin.form.select name="is_active" label="Status">
                    <option value="1" @selected(old('is_active', true))>Active</option>
                    <option value="0">Inactive</option>
                </x-admin.form.select>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-category-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
