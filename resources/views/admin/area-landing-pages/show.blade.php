<x-admin-layout title="Landing Page Detail">
    <x-admin-page-header :title="$page->headline"
        :breadcrumbs="[['label' => 'Area Landing Pages', 'route' => 'admin.area-landing-pages.index'], ['label' => $page->headline]]"
        description="Edit zone landing page content.">
        <x-slot name="actions">
            @can('seo.edit')
                <form method="POST" action="{{ route('admin.area-landing-pages.destroy', $page) }}"
                      data-confirm="Delete this landing page?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <x-admin-card title="Content">
        <form method="POST" action="{{ route('admin.area-landing-pages.update', $page) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="headline" label="Headline" :value="old('headline', $page->headline)" required />
                <x-admin.form.input name="subheadline" label="Subheadline" :value="old('subheadline', $page->subheadline)" />
            </div>
            <x-admin.form.textarea name="content" label="Content" rows="8">{{ old('content', $page->content) }}</x-admin.form.textarea>
            <div>
                @if ($page->featured_image)
                    @php $img = str_starts_with($page->featured_image, 'http') ? $page->featured_image : asset('storage/'.$page->featured_image); @endphp
                    <img src="{{ $img }}" alt="" class="mb-2 h-24 w-40 rounded-lg object-cover">
                @endif
                <x-admin.form.input name="featured_image" label="Featured Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
            </div>
            <x-admin.form.select name="is_active" label="Status">
                <option value="1" @selected(old('is_active', $page->is_active))>Active</option>
                <option value="0" @selected(!old('is_active', $page->is_active))>Inactive</option>
            </x-admin.form.select>

            @if($page->features)
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Features</label>
                    <div class="space-y-2">
                        @foreach($page->features as $i => $feature)
                            <x-admin.form.input name="features[]" :value="$feature" />
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.area-landing-pages.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>

    <div class="mt-4 bg-white dark:bg-night-900 rounded-2xl border border-slate-200 dark:border-night-700 p-5">
        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Zone:</span> {{ $page->zone?->name ?? '—' }} ({{ $page->zone?->type ?? '—' }})</p>
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Created:</span> {{ $page->created_at->format('M d, Y h:i A') }}</p>
            <p><span class="font-medium text-slate-600 dark:text-slate-300">Updated:</span> {{ $page->updated_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</x-admin-layout>
