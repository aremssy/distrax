<x-admin-layout title="Project Categories">
    <x-admin-page-header title="Project Categories"
        :breadcrumbs="[['label' => 'Projects', 'route' => 'admin.projects.index'], ['label' => 'Categories']]"
        description="Manage categories used to organize development projects.">
        <x-slot name="actions">
            <x-admin.export-button resource="project-categories" />
            <a href="{{ route('admin.projects.index') }}"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'w-4 h-4 text-slate-400'])
                Back to Projects
            </a>
            @can('projects.create')
                <button onclick="openModal('create-category-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Category
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
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Category</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Projects</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800" data-sortable data-sortable-url="{{ route('admin.projects.categories.reorder') }}" data-sortable-offset="{{ ($categories->currentPage() - 1) * $categories->perPage() }}">
                    @forelse ($categories as $category)
                        @php
                            $imageUrl = $category->image
                                ? (str_starts_with($category->image, 'http') ? $category->image : asset('storage/'.$category->image))
                                : null;
                        @endphp
                        <tr data-id="{{ $category->id }}" class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-3 py-3.5 align-middle">@include('admin.partials.drag-handle')</td>
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $category->name }}" loading="lazy"
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
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $category->name }}</p>
                                        <p class="mt-0.5 truncate font-mono text-xs text-slate-400 dark:text-night-500">{{ $category->slug }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $category->projects_count }}
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
                                        @can('projects.edit')
                                            <button type="button" onclick="openModal('edit-category-{{ $category->id }}')"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </button>
                                        @endcan

                                        @can('projects.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.projects.categories.destroy', $category) }}"
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
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-400">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('projects.edit')
        @foreach ($categories as $category)
            <x-admin-modal id="edit-category-{{ $category->id }}" title="Edit Category">
                <form method="POST" action="{{ route('admin.projects.categories.update', $category) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    <x-admin.form.input name="name" label="Name" :value="old('name', $category->name)" required />
                    <x-admin.form.textarea name="description" label="Description" rows="2">{{ old('description', $category->description) }}</x-admin.form.textarea>
                    @if($category->image)
                        <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" class="h-20 w-20 rounded-lg object-cover">
                    @endif
                    <x-admin.form.input name="image" label="Category Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
                    <x-admin.form.select name="is_active" label="Status">
                        <option value="1" @selected(old('is_active', $category->is_active))>Active</option>
                        <option value="0" @selected(! old('is_active', $category->is_active))>Inactive</option>
                    </x-admin.form.select>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('edit-category-{{ $category->id }}')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
                    </div>
                </form>
            </x-admin-modal>
        @endforeach
    @endcan

    @can('projects.create')
        <x-admin-modal id="create-category-modal" title="Create Category">
            <form method="POST" action="{{ route('admin.projects.categories.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.textarea name="description" label="Description" rows="2">{{ old('description') }}</x-admin.form.textarea>
                <x-admin.form.input name="image" label="Category Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" hint="Maximum 2 MB." />
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
