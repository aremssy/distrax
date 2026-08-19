<x-admin-layout title="Custom Fields">
    <x-admin-page-header title="Custom Field Builder"
        :breadcrumbs="[['label' => 'Custom Fields']]"
        description="Add your own fields to property listings — no code required.">
        <x-slot name="actions">
            <x-admin.export-button resource="custom-fields" />
        </x-slot>
    </x-admin-page-header>

    {{-- ── How it works ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-night-700 dark:bg-night-900">
        <div class="flex items-center gap-2">
            @include('admin.partials.icon', ['name' => 'sparkles', 'class' => 'w-4.5 h-4.5 text-brand'])
            <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">How custom fields work</h2>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['n' => 1, 'icon' => 'adjustments', 'title' => 'You define a field here', 'body' => 'Pick a property type (Rent, Sale…) and add a field like “Floor Level” or “Pet Friendly”.'],
                ['n' => 2, 'icon' => 'document-text', 'title' => 'It appears on the listing form', 'body' => 'When someone creates or edits that type of listing, your field shows up as an input to fill in.'],
                ['n' => 3, 'icon' => 'magnifying-glass', 'title' => 'It shows on the property page & search', 'body' => 'The saved value is displayed to visitors. Mark it “Filterable” to also let them search by it.'],
            ] as $step)
                <div class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-brand dark:bg-brand/15 dark:text-indigo-300">{{ $step['n'] }}</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $step['title'] }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $step['body'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Property type tabs --}}
    <div class="mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Each property type has its own set of fields:</div>
    <div class="mb-5 flex flex-wrap items-center gap-1">
        @foreach(array_keys(\App\Enums\PropertyType::options()) as $t)
            <a href="{{ route('admin.custom-fields.index', ['type' => $t]) }}"
               class="capitalize {{ $selectedType === $t
                   ? 'bg-brand text-white rounded-lg px-3.5 py-2 text-sm font-semibold'
                   : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-night-800 rounded-lg px-3.5 py-2 text-sm font-semibold' }}">
                {{ ucfirst($t) }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ── Add new field form ──────────────────────────────────────────── --}}
        <div>
            <x-admin-card title="Add a Field to {{ ucfirst($selectedType) }}">
                <form method="POST" action="{{ route('admin.custom-fields.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="property_type" value="{{ $selectedType }}">

                    <x-admin.form.input name="label" label="Field name" :value="old('label')"
                        placeholder="e.g. Floor Level"
                        hint="What the person filling the form will see." required />

                    <x-admin.form.select name="type" label="Answer type" required
                        hint="How they'll enter the value.">
                        @foreach(['text' => 'Text (short line)', 'number' => 'Number', 'select' => 'Dropdown (pick one)',
                                  'multiselect' => 'Dropdown (pick many)', 'checkbox' => 'Checkboxes', 'radio' => 'Radio (pick one)',
                                  'date' => 'Date', 'textarea' => 'Text (paragraph)'] as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('type') === $val)>{{ $lbl }}</option>
                        @endforeach
                    </x-admin.form.select>

                    <div x-data="{ type: '{{ old('type', 'text') }}' }"
                         x-on:change.window="if ($event.target.name === 'type') type = $event.target.value">
                        <div x-show="['select','multiselect','radio','checkbox'].includes(type)" x-cloak>
                            <x-admin.form.textarea name="options" label="Choices" rows="4"
                                :value="old('options')" placeholder="Ground floor&#10;First floor&#10;Second floor"
                                hint="One choice per line." />
                        </div>
                    </div>

                    <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-night-800 dark:bg-night-800/40">
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" name="is_required" value="1" @checked(old('is_required'))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                            <span>
                                <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Required</span>
                                <span class="block text-xs text-slate-400 dark:text-slate-500">Must be filled in before the listing can be saved.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable'))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                            <span>
                                <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Filterable</span>
                                <span class="block text-xs text-slate-400 dark:text-slate-500">Visitors can filter the property search by this field.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                            <span>
                                <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Active</span>
                                <span class="block text-xs text-slate-400 dark:text-slate-500">Turn off to hide the field without deleting it.</span>
                            </span>
                        </label>
                    </div>

                    <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                        Add Field
                    </button>
                </form>
            </x-admin-card>
        </div>

        {{-- ── Field list ──────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-night-800">
                    <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">{{ ucfirst($selectedType) }} fields <span class="text-slate-400 dark:text-night-500">({{ $fields->count() }})</span></h2>
                    @if ($fields->count() > 1)
                        <span class="flex items-center gap-1.5 text-xs text-slate-400 dark:text-night-500">
                            @include('admin.partials.icon', ['name' => 'grip-dots', 'class' => 'w-4 h-4'])
                            Drag rows to reorder — saved automatically
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                                <th class="w-10 px-3 py-3.5"></th>
                                <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Field</th>
                                <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Answer type</th>
                                <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500" title="How this field behaves">Behaviour</th>
                                <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                                <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-night-800"
                               data-sortable
                               data-sortable-url="{{ route('admin.custom-fields.reorder') }}"
                               data-sortable-type="{{ $selectedType }}">
                            @forelse($fields as $field)
                                <tr data-id="{{ $field->id }}" class="group transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                                    <td class="px-3 py-3.5 align-middle">
                                        <button type="button" data-drag-handle
                                                class="cursor-grab touch-none rounded-md p-1 text-slate-300 transition-colors hover:text-slate-500 active:cursor-grabbing dark:text-night-600 dark:hover:text-slate-400"
                                                title="Drag to reorder" aria-label="Drag to reorder">
                                            @include('admin.partials.icon', ['name' => 'grip-dots', 'class' => 'w-5 h-5'])
                                        </button>
                                    </td>
                                    <td class="min-w-44 px-3 py-3.5">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $field->label }}</p>
                                        <p class="mt-0.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $field->key }}</p>
                                        @if($field->hasOptions() && $field->options)
                                            <p class="mt-1 text-xs text-slate-400 dark:text-night-500">Choices: {{ implode(', ', array_slice($field->options, 0, 3)) }}{{ count($field->options) > 3 ? '…' : '' }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5">
                                        <x-admin-badge color="indigo">{{ $field->type }}</x-admin-badge>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex flex-wrap gap-1.5">
                                            @if($field->is_required)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-600 dark:bg-amber-500/15 dark:text-amber-400" title="Must be filled in on the listing form">Required</span>
                                            @endif
                                            @if($field->is_filterable)
                                                <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-600 dark:bg-sky-500/15 dark:text-sky-400" title="Visitors can filter search by this field">In search</span>
                                            @endif
                                            @unless($field->is_required || $field->is_filterable)
                                                <span class="text-xs text-slate-300 dark:text-night-600">—</span>
                                            @endunless
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5">
                                        <x-admin-badge :color="$field->is_active ? 'green' : 'gray'">
                                            {{ $field->is_active ? 'Active' : 'Hidden' }}
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
                                                <button type="button" onclick="openModal('edit-field-{{ $field->id }}')"
                                                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </button>
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.custom-fields.destroy', $field) }}"
                                                      data-confirm="Delete field {{ addslashes($field->label) }}? All saved values will also be deleted.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">
                                        No fields yet for <strong class="capitalize">{{ $selectedType }}</strong>. Add your first one using the form on the left.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Edit modals ─────────────────────────────────────────────────────── --}}
    @foreach($fields as $field)
        <x-admin-modal id="edit-field-{{ $field->id }}" title="Edit: {{ $field->label }}" maxWidth="md">
            <form method="POST" action="{{ route('admin.custom-fields.update', $field) }}" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="property_type" value="{{ $field->property_type }}">
                {{-- Order is managed by drag-and-drop; preserve the current value here. --}}
                <input type="hidden" name="sort_order" value="{{ $field->sort_order }}">

                <x-admin.form.input name="label" label="Field name" :value="$field->label" required />

                <x-admin.form.select name="type" label="Answer type">
                    @foreach(['text' => 'Text (short line)', 'number' => 'Number', 'select' => 'Dropdown (pick one)',
                              'multiselect' => 'Dropdown (pick many)', 'checkbox' => 'Checkboxes', 'radio' => 'Radio (pick one)',
                              'date' => 'Date', 'textarea' => 'Text (paragraph)'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($field->type === $val)>{{ $lbl }}</option>
                    @endforeach
                </x-admin.form.select>

                @if($field->hasOptions())
                    <x-admin.form.textarea name="options" label="Choices (one per line)" rows="4"
                        :value="implode(chr(10), $field->options ?? [])" />
                @endif

                <div class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-night-800 dark:bg-night-800/40">
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" name="is_required" value="1" @checked($field->is_required)
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        <span>
                            <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Required</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">Must be filled in before the listing can be saved.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" name="is_filterable" value="1" @checked($field->is_filterable)
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        <span>
                            <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Filterable</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">Visitors can filter the property search by this field.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" name="is_active" value="1" @checked($field->is_active)
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        <span>
                            <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Active</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">Turn off to hide the field without deleting it.</span>
                        </span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('edit-field-{{ $field->id }}')"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </form>
        </x-admin-modal>
    @endforeach
</x-admin-layout>
