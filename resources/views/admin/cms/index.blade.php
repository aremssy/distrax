<x-admin-layout title="CMS Pages">
    <x-admin-page-header title="CMS Pages"
        :breadcrumbs="[['label' => 'CMS']]"
        description="Manage static content pages.">
        <x-slot name="actions">
            <x-admin.export-button resource="cms" />
            @can('cms.create')
                <button onclick="openModal('create-cms-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Page
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.cms.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Statuses</option>
                @foreach (['draft', 'published', 'archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status']))
                <a href="{{ route('admin.cms.index') }}"
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
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Page</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Author</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Updated</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($pages as $page)
                        @php
                            $imageUrl = $page->featured_image
                                ? (str_starts_with($page->featured_image, 'http') ? $page->featured_image : asset('storage/'.$page->featured_image))
                                : null;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $page->title }}" loading="lazy"
                                             class="h-11 w-14 shrink-0 rounded-lg object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @else
                                        <div class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'document-text', 'class' => 'w-5 h-5'])
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.cms.show', $page) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $page->title }}
                                        </a>
                                        <p class="mt-0.5 truncate font-mono text-xs text-slate-400 dark:text-night-500">{{ $page->slug }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $page->creator?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($page->status) { 'published' => 'green', 'draft' => 'yellow', 'archived' => 'gray', default => 'gray' }">
                                    {{ ucfirst($page->status) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $page->updated_at->format('M d, Y') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.cms.show', $page) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('cms.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.cms.destroy', $page) }}"
                                                  data-confirm="Delete this page?">
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
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-400">No pages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pages->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('cms.create')
        <x-admin-modal id="create-cms-modal" title="Create Page">
            <form method="POST" action="{{ route('admin.cms.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-admin.form.input name="title" label="Title" :value="old('title')" required />
                <x-admin.form.input name="slug" label="Slug" :value="old('slug')" hint="Leave blank to auto-generate" />
                <x-admin.form.rich-text name="content" label="Content" placeholder="Write the page content…">{{ old('content') }}</x-admin.form.rich-text>
                <x-admin.form.input name="meta_title" label="Meta Title" :value="old('meta_title')" />
                <x-admin.form.textarea name="meta_description" label="Meta Description" rows="2">{{ old('meta_description') }}</x-admin.form.textarea>
                <x-admin.form.input name="featured_image" label="Featured Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB." />
                <x-admin.form.select name="status" label="Status">
                    <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status') === 'published')>Published</option>
                    <option value="archived">Archived</option>
                </x-admin.form.select>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-cms-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
