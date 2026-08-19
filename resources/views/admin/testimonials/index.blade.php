<x-admin-layout title="Testimonials">
    <x-admin-page-header title="Testimonials"
        :breadcrumbs="[['label' => 'Testimonials']]"
        description="Manage the client quotes shown on the homepage.">
        <x-slot name="actions">
            <x-admin.export-button resource="testimonials" />
            @can('testimonials.create')
                <button onclick="openModal('create-testimonial-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Testimonial
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        @if ($testimonials->total() > 1)
            <div class="flex justify-end border-b border-slate-100 px-5 py-3 dark:border-night-800">@include('admin.partials.reorder-hint')</div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 px-3 py-3.5"></th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Author</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Quote</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Rating</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800" data-sortable data-sortable-url="{{ route('admin.testimonials.reorder') }}" data-sortable-offset="{{ ($testimonials->currentPage() - 1) * $testimonials->perPage() }}">
                    @forelse ($testimonials as $testimonial)
                        @php
                            $avatarUrl = $testimonial->avatar
                                ? (str_starts_with($testimonial->avatar, 'http') ? $testimonial->avatar : asset('storage/'.$testimonial->avatar))
                                : null;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40" data-id="{{ $testimonial->id }}">
                            <td class="px-3 py-3.5 align-middle">@include('admin.partials.drag-handle')</td>
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $testimonial->name }}" loading="lazy"
                                             class="h-11 w-11 shrink-0 rounded-full object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @else
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.testimonials.show', $testimonial) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $testimonial->name }}
                                        </a>
                                        <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $testimonial->role ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="max-w-xs px-3 py-3.5">
                                <p class="truncate text-sm text-slate-600 dark:text-slate-300">{{ $testimonial->quote }}</p>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @include('admin.partials.icon', [
                                            'name' => 'star',
                                            'class' => 'w-3.5 h-3.5 '.($i <= (int) $testimonial->rating ? 'text-amber-500' : 'text-slate-200 dark:text-night-700'),
                                        ])
                                    @endfor
                                    <span class="ml-1.5 text-xs text-slate-400 dark:text-night-500">{{ $testimonial->rating }}/5</span>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$testimonial->is_active ? 'green' : 'gray'">
                                    {{ $testimonial->is_active ? 'Active' : 'Inactive' }}
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
                                        <a href="{{ route('admin.testimonials.show', $testimonial) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('testimonials.edit')
                                            <a href="{{ route('admin.testimonials.show', $testimonial) }}"
                                               class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                Edit
                                            </a>
                                        @endcan

                                        @can('testimonials.delete')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}"
                                                  data-confirm="Delete this testimonial?">
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
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No testimonials found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $testimonials->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('testimonials.create')
        <x-admin-modal id="create-testimonial-modal" title="Create Testimonial">
            <form method="POST" action="{{ route('admin.testimonials.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.input name="role" label="Role" :value="old('role')" placeholder="Tenant, Owner, Buyer..." />
                <x-admin.form.textarea name="quote" label="Quote" :rows="3" required>{{ old('quote') }}</x-admin.form.textarea>
                <x-admin.form.input name="avatar" label="Avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB." />
                <x-admin.form.select name="rating" label="Rating">
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected(old('rating', 5) == $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </x-admin.form.select>
                <x-admin.form.select name="is_active" label="Status">
                    <option value="1" @selected(old('is_active', true))>Active</option>
                    <option value="0">Inactive</option>
                </x-admin.form.select>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-testimonial-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
