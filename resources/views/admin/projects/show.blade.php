<x-admin-layout title="Project Detail">
    <x-admin-page-header :title="$project->title"
        :breadcrumbs="[['label' => 'Projects', 'route' => 'admin.projects.index'], ['label' => $project->title]]"
        description="Edit project details.">
        <x-slot name="actions">
            @can('projects.delete')
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                      data-confirm="Delete this project?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <x-admin-card title="Project Details">
        <form method="POST" action="{{ route('admin.projects.update', $project) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <x-admin.form.input name="title" label="Title" :value="old('title', $project->title)" required />
            <x-admin.form.select name="project_category_id" label="Category">
                <option value="">— None —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('project_category_id', $project->project_category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-admin.form.select>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="developer_name" label="Developer Name" :value="old('developer_name', $project->developer_name)" />
                <x-admin.form.select name="zone_id" label="Zone">
                    <option value="">— None —</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" @selected(old('zone_id', $project->zone_id) == $zone->id)>{{ $zone->name }}</option>
                    @endforeach
                </x-admin.form.select>
            </div>
            <x-admin.form.rich-text name="description" label="Description">{{ old('description', $project->description) }}</x-admin.form.rich-text>
            <div>
                @if ($project->cover_image)
                    @php
                        $coverImageUrl = str_starts_with($project->cover_image, 'http') ? $project->cover_image : asset('storage/'.$project->cover_image);
                    @endphp
                    <img src="{{ $coverImageUrl }}" alt="{{ $project->title }}" class="mb-2 h-24 w-40 rounded-lg object-cover">
                @endif
                <x-admin.form.input name="cover_image" label="Cover Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="price_from" label="Price From" type="number" step="0.01" :value="old('price_from', $project->price_from)" />
                <x-admin.form.input name="completion_date" label="Completion Date" type="date" :value="old('completion_date', $project->completion_date?->format('Y-m-d'))" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.select name="status" label="Status">
                    <option value="upcoming" @selected(old('status', $project->status) === 'upcoming')>Upcoming</option>
                    <option value="ongoing" @selected(old('status', $project->status) === 'ongoing')>Ongoing</option>
                    <option value="completed" @selected(old('status', $project->status) === 'completed')>Completed</option>
                </x-admin.form.select>
                <x-admin.form.input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', $project->sort_order)" />
            </div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                    @checked(old('is_featured', $project->is_featured))
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_featured" class="text-sm font-medium text-slate-700 dark:text-slate-300">Featured on homepage</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.projects.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>
</x-admin-layout>
