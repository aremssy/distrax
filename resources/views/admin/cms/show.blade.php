<x-admin-layout title="CMS Page Detail">
    <x-admin-page-header :title="$page->title"
        :breadcrumbs="[['label' => 'CMS', 'route' => 'admin.cms.index'], ['label' => $page->title]]"
        description="Edit CMS page content and settings.">
        <x-slot name="actions">
            @can('cms.delete')
                <form method="POST" action="{{ route('admin.cms.destroy', $page) }}"
                      data-confirm="Delete this page?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-admin-card title="Content">
                <form method="POST" action="{{ route('admin.cms.update', $page) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <x-admin.form.input name="title" label="Title" :value="old('title', $page->title)" required />
                        <x-admin.form.input name="slug" label="Slug" :value="old('slug', $page->slug)" />
                        <x-admin.form.rich-text name="content" label="Content" :rows="14">{{ old('content', $page->content) }}</x-admin.form.rich-text>
                        <div>
                            @if ($page->featured_image)
                                @php $img = str_starts_with($page->featured_image, 'http') ? $page->featured_image : asset('storage/'.$page->featured_image); @endphp
                                <img src="{{ $img }}" alt="" class="mb-2 h-24 w-40 rounded-lg object-cover">
                            @endif
                            <x-admin.form.input name="featured_image" label="Featured Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
                        </div>
                        <x-admin.form.select name="status" label="Status">
                            <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                            <option value="archived" @selected(old('status', $page->status) === 'archived')>Archived</option>
                        </x-admin.form.select>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update Page</button>
                        </div>
                    </div>
                </form>
            </x-admin-card>
        </div>

        <div class="space-y-6">
            <x-admin-card title="SEO Meta">
                <form method="POST" action="{{ route('admin.cms.update', $page) }}">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <x-admin.form.input name="meta_title" label="Meta Title" :value="old('meta_title', $page->meta_title)" />
                        <x-admin.form.textarea name="meta_description" label="Meta Description" rows="3">{{ old('meta_description', $page->meta_description) }}</x-admin.form.textarea>
                        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                            <p>Created by: {{ $page->creator?->name ?? '—' }}</p>
                            <p>Created: {{ $page->created_at->format('M d, Y h:i A') }}</p>
                            <p>Updated: {{ $page->updated_at->format('M d, Y h:i A') }}</p>
                            @if($page->published_at)
                                <p>Published: {{ $page->published_at->format('M d, Y h:i A') }}</p>
                            @endif
                        </div>
                    </div>
                </form>
            </x-admin-card>

            @if($page->seoMeta)
                <x-admin-card title="SEO Record">
                    <div class="space-y-2 break-words text-xs text-slate-500 dark:text-slate-400">
                        @if($page->seoMeta->meta_title)<p><span class="font-medium text-slate-600 dark:text-slate-300">Title:</span> {{ $page->seoMeta->meta_title }}</p>@endif
                        @if($page->seoMeta->meta_description)<p><span class="font-medium text-slate-600 dark:text-slate-300">Desc:</span> {{ $page->seoMeta->meta_description }}</p>@endif
                        @if($page->seoMeta->og_title)<p><span class="font-medium text-slate-600 dark:text-slate-300">OG Title:</span> {{ $page->seoMeta->og_title }}</p>@endif
                        @if($page->seoMeta->og_description)<p><span class="font-medium text-slate-600 dark:text-slate-300">OG Desc:</span> {{ $page->seoMeta->og_description }}</p>@endif
                    </div>
                </x-admin-card>
            @endif
        </div>
    </div>
</x-admin-layout>
