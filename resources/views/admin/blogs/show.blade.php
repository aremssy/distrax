<x-admin-layout title="Blog Post Detail">
    <x-admin-page-header :title="$post->title"
        :breadcrumbs="[['label' => 'Blog', 'route' => 'admin.blogs.index'], ['label' => $post->title]]"
        description="Edit blog post content and settings.">
        <x-slot name="actions">
            @can('blogs.delete')
                <form method="POST" action="{{ route('admin.blogs.destroy', $post) }}"
                      data-confirm="Delete this post?">
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
                <form method="POST" action="{{ route('admin.blogs.update', $post) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <x-admin.form.input name="title" label="Title" :value="old('title', $post->title)" required />
                        <x-admin.form.input name="slug" label="Slug" :value="old('slug', $post->slug)" />
                        <x-admin.form.rich-text name="content" label="Content" :rows="14">{{ old('content', $post->content) }}</x-admin.form.rich-text>
                        <x-admin.form.textarea name="excerpt" label="Excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</x-admin.form.textarea>
                        @if ($post->featured_image)
                            @php
                                $currentImage = str_starts_with($post->featured_image, 'http')
                                    ? $post->featured_image
                                    : asset('storage/'.$post->featured_image);
                            @endphp
                            <img src="{{ $currentImage }}" alt="{{ $post->title }}" class="h-24 w-40 rounded-lg object-cover">
                        @endif
                        <x-admin.form.input name="featured_image" label="Featured Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new image replaces the current one." />
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update Post</button>
                        </div>
                    </div>
                </form>
            </x-admin-card>
        </div>

        <div class="space-y-6">
            <x-admin-card title="Settings">
                <form method="POST" action="{{ route('admin.blogs.update', $post) }}">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <x-admin.form.select name="blog_category_id" label="Category">
                            <option value="">None</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('blog_category_id', $post->blog_category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </x-admin.form.select>
                        <x-admin.form.select name="status" label="Status">
                            <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                            <option value="archived" @selected(old('status', $post->status) === 'archived')>Archived</option>
                        </x-admin.form.select>
                        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                            <p>Author: {{ $post->author?->name ?? '—' }}</p>
                            <p>Created: {{ $post->created_at->format('M d, Y h:i A') }}</p>
                            @if($post->published_at)
                                <p>Published: {{ $post->published_at->format('M d, Y h:i A') }}</p>
                            @endif
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save</button>
                        </div>
                    </div>
                </form>
            </x-admin-card>

            <x-admin-card title="SEO Meta">
                <form method="POST" action="{{ route('admin.blogs.update', $post) }}">
                    @csrf @method('PUT')
                    <div class="space-y-4">
                        <x-admin.form.input name="meta_title" label="Meta Title" :value="old('meta_title', $post->meta_title)" />
                        <x-admin.form.textarea name="meta_description" label="Meta Description" rows="2">{{ old('meta_description', $post->meta_description) }}</x-admin.form.textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save SEO</button>
                        </div>
                    </div>
                </form>
            </x-admin-card>

            @if($post->tags)
                <x-admin-card title="Tags">
                    <div class="flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <x-admin-badge color="indigo">{{ $tag }}</x-admin-badge>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>
    </div>
</x-admin-layout>
