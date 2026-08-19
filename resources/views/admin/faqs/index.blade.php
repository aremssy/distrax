<x-admin-layout title="FAQs">
    <x-admin-page-header title="FAQ Management"
        :breadcrumbs="[['label' => 'FAQs']]"
        description="Questions and answers shown on the public FAQ page.">
        <x-slot name="actions">
            <x-admin.export-button resource="faqs" />
            <a href="{{ route('faq') }}" target="_blank" rel="noopener"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                View public page
            </a>
            @can('cms.create')
                <button onclick="openModal('create-faq-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Add FAQ
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    @if ($categories->isNotEmpty() || request()->filled('category'))
        <form method="GET" action="{{ route('admin.faqs.index') }}"
              class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
            <div class="flex flex-wrap items-center gap-3">
                <select name="category" onchange="this.form.submit()"
                        class="max-w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>

                @if (request()->hasAny(['category']))
                    <a href="{{ route('admin.faqs.index') }}"
                       class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                        Clear
                    </a>
                @endif
            </div>
        </form>
    @endif

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        @if ($faqs->total() > 1)
            <div class="flex justify-end border-b border-slate-100 px-5 py-3 dark:border-night-800">
                @include('admin.partials.reorder-hint')
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 px-3 py-3.5"></th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Question</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Answer</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800"
                       data-sortable
                       data-sortable-url="{{ route('admin.faqs.reorder') }}"
                       data-sortable-offset="{{ ($faqs->currentPage() - 1) * $faqs->perPage() }}">
                    @forelse ($faqs as $faq)
                        <tr data-id="{{ $faq->id }}" class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-3 py-3.5 align-middle">
                                @include('admin.partials.drag-handle')
                            </td>
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $faq->question }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $faq->category ?? 'Uncategorized' }}</p>
                                </div>
                            </td>

                            <td class="px-3 py-3.5">
                                <p class="max-w-sm truncate text-xs text-slate-500 dark:text-slate-400">{{ $faq->answer }}</p>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$faq->is_active ? 'green' : 'gray'">
                                    {{ $faq->is_active ? 'Active' : 'Inactive' }}
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
                                        @can('cms.edit')
                                            <button type="button" onclick="openModal('edit-faq-{{ $faq->id }}')"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </button>
                                        @endcan

                                        @can('cms.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}"
                                                  data-confirm="Delete this FAQ?">
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
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-400">No FAQs yet. Add your first question.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $faqs->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('cms.create')
        <x-admin-modal id="create-faq-modal" title="Add a question">
            <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-4">
                @csrf
                <x-admin.form.input name="question" label="Question" required />
                <x-admin.form.input name="category" label="Category" placeholder="e.g. Payments" :value="old('category')" />
                <x-admin.form.textarea name="answer" label="Answer" rows="3" required>{{ old('answer') }}</x-admin.form.textarea>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-faq-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Add FAQ</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan

    @can('cms.edit')
        @foreach ($faqs as $faq)
            <x-admin-modal id="edit-faq-{{ $faq->id }}" title="Edit FAQ">
                <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <x-admin.form.input name="question" label="Question" :value="$faq->question" required />
                    <x-admin.form.input name="category" label="Category" :value="$faq->category" />
                    <x-admin.form.textarea name="answer" label="Answer" rows="3" required>{{ $faq->answer }}</x-admin.form.textarea>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($faq->is_active)
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('edit-faq-{{ $faq->id }}')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save</button>
                    </div>
                </form>
            </x-admin-modal>
        @endforeach
    @endcan
</x-admin-layout>
