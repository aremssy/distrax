<x-admin-layout title="SEO Meta Detail">
    <x-admin-page-header title="SEO Meta #{{ $meta->id }}"
        :breadcrumbs="[['label' => 'SEO', 'route' => 'admin.seo.index'], ['label' => 'SEO #'.$meta->id]]"
        description="Edit SEO metadata." />

    <x-admin-card title="Edit SEO Meta">
        <form method="POST" action="{{ route('admin.seo.update', $meta) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="meta_title" label="Meta Title" :value="old('meta_title', $meta->meta_title)" />
                <x-admin.form.input name="og_title" label="OG Title" :value="old('og_title', $meta->og_title)" />
            </div>
            <x-admin.form.textarea name="meta_description" label="Meta Description" rows="3">{{ old('meta_description', $meta->meta_description) }}</x-admin.form.textarea>
            <x-admin.form.textarea name="og_description" label="OG Description" rows="3">{{ old('og_description', $meta->og_description) }}</x-admin.form.textarea>
            <div>
                @if ($meta->og_image)
                    @php $img = str_starts_with($meta->og_image, 'http') ? $meta->og_image : asset('storage/'.$meta->og_image); @endphp
                    <img src="{{ $img }}" alt="" class="mb-2 h-24 w-40 rounded-lg object-cover">
                @endif
                <x-admin.form.input name="og_image" label="OG Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.seo.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>

    <div class="mt-4 bg-white dark:bg-night-900 rounded-2xl border border-slate-200 dark:border-night-700 p-5">
        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Type:</span> {{ class_basename($meta->seoable_type) }}</p>
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Related ID:</span> {{ $meta->seoable_id }}</p>
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Created:</span> {{ $meta->created_at->format('M d, Y h:i A') }}</p>
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Updated:</span> {{ $meta->updated_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</x-admin-layout>
