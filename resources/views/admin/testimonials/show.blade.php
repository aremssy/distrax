<x-admin-layout title="Testimonial Detail">
    <x-admin-page-header :title="$testimonial->name"
        :breadcrumbs="[['label' => 'Testimonials', 'route' => 'admin.testimonials.index'], ['label' => $testimonial->name]]"
        description="Edit testimonial details.">
        <x-slot name="actions">
            @can('testimonials.delete')
                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}"
                      data-confirm="Delete this testimonial?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <x-admin-card title="Testimonial Details">
        <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" :value="old('name', $testimonial->name)" required />
                <x-admin.form.input name="role" label="Role" :value="old('role', $testimonial->role)" />
            </div>
            <x-admin.form.textarea name="quote" label="Quote" :rows="4" required>{{ old('quote', $testimonial->quote) }}</x-admin.form.textarea>
            <div>
                @if ($testimonial->avatar)
                    <img src="{{ str_starts_with($testimonial->avatar, 'http') ? $testimonial->avatar : asset('storage/'.$testimonial->avatar) }}"
                         alt="{{ $testimonial->name }}"
                         class="mb-2 h-16 w-16 rounded-full object-cover">
                @endif
                <x-admin.form.input name="avatar" label="Avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB." />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.select name="rating" label="Rating">
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected(old('rating', $testimonial->rating) == $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </x-admin.form.select>
                <x-admin.form.select name="is_active" label="Status">
                    <option value="1" @selected(old('is_active', $testimonial->is_active))>Active</option>
                    <option value="0" @selected(!old('is_active', $testimonial->is_active))>Inactive</option>
                </x-admin.form.select>
            </div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                    @checked(old('is_featured', $testimonial->is_featured))
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_featured" class="text-sm font-medium text-slate-700 dark:text-slate-300">Featured</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.testimonials.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>
</x-admin-layout>
