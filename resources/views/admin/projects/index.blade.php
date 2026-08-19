<x-admin-layout title="Projects">
    <x-admin-page-header title="Projects"
        :breadcrumbs="[['label' => 'Projects']]"
        description="Manage new development projects shown on the homepage and /projects.">
        <x-slot name="actions">
            <x-admin.export-button resource="projects" />
            <a href="{{ route('admin.projects.categories.index') }}"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                Categories
            </a>
            @can('projects.create')
                <button onclick="openModal('create-project-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Project
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.projects.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All statuses</option>
                @foreach (['upcoming', 'ongoing', 'completed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="category_id" onchange="this.form.submit()"
                    class="max-w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status', 'category_id']))
                <a href="{{ route('admin.projects.index') }}"
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
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Project</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Category</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Zone</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Price From</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($projects as $project)
                        @php
                            $imageUrl = $project->cover_image
                                ? (str_starts_with($project->cover_image, 'http') ? $project->cover_image : asset('storage/'.$project->cover_image))
                                : null;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $project->title }}" loading="lazy"
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
                                        <a href="{{ route('admin.projects.show', $project) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $project->title }}
                                        </a>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'map-pin', 'class' => 'w-3.5 h-3.5 shrink-0 text-amber-500'])
                                            <span class="truncate">{{ $project->zone?->name ?? '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $project->category?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $project->zone?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $project->price_from ? money($project->price_from) : '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($project->status) { 'completed' => 'green', 'ongoing' => 'blue', default => 'yellow' }">
                                    {{ ucfirst($project->status) }}
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
                                        <a href="{{ route('admin.projects.show', $project) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('projects.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                                                  data-confirm="Delete this project?">
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
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No projects found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $projects->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('projects.create')
        <x-admin-modal id="create-project-modal" title="Create Project">
            <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-admin.form.input name="title" label="Title" :value="old('title')" required />
                <x-admin.form.select name="project_category_id" label="Category">
                    <option value="">— None —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('project_category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-admin.form.select>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-admin.form.input name="developer_name" label="Developer Name" :value="old('developer_name')" />
                    <x-admin.form.select name="zone_id" label="Zone">
                        <option value="">— None —</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('zone_id') == $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </x-admin.form.select>
                </div>
                <x-admin.form.rich-text name="description" label="Description" placeholder="Describe the project…">{{ old('description') }}</x-admin.form.rich-text>
                <x-admin.form.input name="cover_image" label="Cover Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB." />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-admin.form.input name="price_from" label="Price From" type="number" step="0.01" :value="old('price_from')" />
                    <x-admin.form.input name="completion_date" label="Completion Date" type="date" :value="old('completion_date')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-admin.form.select name="status" label="Status">
                        <option value="upcoming" @selected(old('status', 'upcoming') === 'upcoming')>Upcoming</option>
                        <option value="ongoing" @selected(old('status') === 'ongoing')>Ongoing</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    </x-admin.form.select>
                    <x-admin.form.input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', 0)" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-project-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
