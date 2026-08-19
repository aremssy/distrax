<x-admin-layout title="Banner Detail">
    <x-admin-page-header :title="$banner->name"
        :breadcrumbs="[['label' => 'Banners', 'route' => 'admin.banners.index'], ['label' => $banner->name]]"
        description="Edit banner details.">
        <x-slot name="actions">
            @can('banners.delete')
                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}"
                      data-confirm="Delete this banner?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <x-admin-card title="Banner Details">
        <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" :value="old('name', $banner->name)" required />
                <x-admin.form.select name="position" label="Position">
                    <option value="hero" @selected($banner->position === 'hero')>Hero</option>
                    <option value="sidebar" @selected($banner->position === 'sidebar')>Sidebar</option>
                    <option value="inline" @selected($banner->position === 'inline')>Inline</option>
                    <option value="popup" @selected($banner->position === 'popup')>Popup</option>
                    <option value="footer" @selected($banner->position === 'footer')>Footer</option>
                </x-admin.form.select>
            </div>
            <div>
                @if ($banner->image)
                    @php
                        $bannerImageUrl = str_starts_with($banner->image, 'http') ? $banner->image : asset('storage/'.$banner->image);
                    @endphp
                    <img src="{{ $bannerImageUrl }}" alt="{{ $banner->name }}" class="mb-2 h-20 w-28 rounded-lg object-cover">
                @endif
                <x-admin.form.input name="image" label="Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
            </div>
            <x-admin.form.input name="link" label="Link URL" :value="old('link', $banner->link)" />
            <x-admin.form.select name="is_active" label="Status">
                <option value="1" @selected(old('is_active', $banner->is_active))>Active</option>
                <option value="0" @selected(!old('is_active', $banner->is_active))>Inactive</option>
            </x-admin.form.select>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="starts_at" label="Starts At" type="date" :value="old('starts_at', $banner->starts_at?->format('Y-m-d'))" />
                <x-admin.form.input name="ends_at" label="Ends At" type="date" :value="old('ends_at', $banner->ends_at?->format('Y-m-d'))" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.banners.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>
</x-admin-layout>
