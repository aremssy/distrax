@php
    $selectedType = old('type', $listing->type ?: 'rent');
    $status = old('status', $listing->status ?: 'pending');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <x-admin-card title="Listing">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.form.input name="title" label="Title" :value="old('title', $listing->title)" required />

            <x-admin.form.select name="type" label="Type" required>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.select name="status" label="Status" required>
                @foreach(['draft','pending','active','rented','sold','archived','rejected'] as $item)
                    <option value="{{ $item }}" @selected($status === $item)>{{ ucfirst($item) }}</option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.select name="owner_id" label="Owner" required>
                <option value="">Select owner</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}" @selected((int) old('owner_id', $listing->owner_id) === $owner->id)>
                        {{ $owner->name }} — {{ $owner->email }}
                    </option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.select name="zone_id" label="Zone" required>
                <option value="">Select zone</option>
                @foreach($zones as $zone)
                    <option value="{{ $zone->id }}" @selected((int) old('zone_id', $listing->zone_id) === $zone->id)>{{ $zone->name }}</option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.input name="language_tag" label="Language" :value="old('language_tag', $listing->language_tag ?: setting('default_language', 'en'))" required />

            <x-admin.form.input name="price" label="Price" type="number" min="0" :value="old('price', $listing->price)" required />

            <x-admin.form.select name="currency_code" label="Currency">
                @foreach($currencies ?? [] as $currency)
                    <option value="{{ $currency->code }}" @selected(old('currency_code', $listing->currency_code ?: 'NGN') === $currency->code)>
                        {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.input name="service_charge" label="Service Charge" type="number" min="0" :value="old('service_charge', $listing->service_charge)" />
            <x-admin.form.input name="advance_months" label="Advance Months" type="number" min="0" :value="old('advance_months', $listing->advance_months)" />

            <label class="flex items-end gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_negotiable" value="1" @checked(old('is_negotiable', $listing->is_negotiable)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                Price is negotiable
            </label>
        </div>

        <div class="mt-4">
            <x-admin.form.rich-text name="description" label="Description" rows="6"
                placeholder="Describe the property — highlights, layout, neighbourhood…"
                hint="Use formatting to make the listing easy to read.">{{ old('description', $listing->description) }}</x-admin.form.rich-text>
        </div>
    </x-admin-card>

    <x-admin-card title="Property Details">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-admin.form.input name="bedrooms" label="Bedrooms" type="number" min="0" :value="old('bedrooms', $listing->bedrooms)" />
            <x-admin.form.input name="bathrooms" label="Bathrooms" type="number" min="0" :value="old('bathrooms', $listing->bathrooms)" />
            <x-admin.form.input name="floor" label="Floor" type="number" min="0" :value="old('floor', $listing->floor)" />
            <x-admin.form.input name="total_floors" label="Total Floors" type="number" min="0" :value="old('total_floors', $listing->total_floors)" />
            <x-admin.form.input name="area_sqft" label="Area Sqft" type="number" min="0" :value="old('area_sqft', $listing->area_sqft)" />

            <x-admin.form.select name="allowed_for" label="Allowed For">
                @foreach(['both' => 'Both', 'family' => 'Family', 'bachelor' => 'Bachelor'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('allowed_for', $listing->allowed_for ?: 'both') === $value)>{{ $label }}</option>
                @endforeach
            </x-admin.form.select>

            <label class="flex items-end gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="parking" value="1" @checked(old('parking', $listing->parking)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                Parking
            </label>

            <label class="flex items-end gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="furnished" value="1" @checked(old('furnished', $listing->furnished)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                Furnished
            </label>
        </div>
    </x-admin-card>

    <x-admin-card title="Location">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <x-admin.form.input name="address" label="Address" :value="old('address', $listing->address)" />
            </div>
            <x-admin.form.input name="lat" label="Latitude" :value="old('lat', $listing->lat)" />
            <x-admin.form.input name="lng" label="Longitude" :value="old('lng', $listing->lng)" />
        </div>
    </x-admin-card>

    <x-admin-card title="Custom Fields">
        @forelse($customFieldsByType as $type => $fields)
            <div data-custom-field-group="{{ $type }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 {{ $selectedType === $type ? '' : 'hidden' }}">
                @foreach($fields as $field)
                    @php
                        $storedValue = $customFieldValues[$field->id] ?? null;
                        $fieldValue = old("custom_fields.{$field->id}", $storedValue);
                        $optionsValue = is_array($fieldValue) ? $fieldValue : array_filter(array_map('trim', explode(',', (string) $fieldValue)));
                    @endphp

                    <div>
                        @if($field->type === 'textarea')
                            <x-admin.form.textarea name="custom_fields[{{ $field->id }}]" :label="$field->label" :required="$field->is_required">{{ $fieldValue }}</x-admin.form.textarea>
                        @elseif($field->type === 'select' || $field->type === 'radio')
                            <x-admin.form.select name="custom_fields[{{ $field->id }}]" :label="$field->label" :required="$field->is_required">
                                <option value="">Select</option>
                                @foreach($field->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected($fieldValue === $option)>{{ $option }}</option>
                                @endforeach
                            </x-admin.form.select>
                        @elseif($field->type === 'multiselect' || $field->type === 'checkbox')
                            <x-admin.form.select name="custom_fields[{{ $field->id }}][]" :label="$field->label" :required="$field->is_required" multiple>
                                @foreach($field->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected(in_array($option, $optionsValue, true))>{{ $option }}</option>
                                @endforeach
                            </x-admin.form.select>
                        @else
                            <x-admin.form.input name="custom_fields[{{ $field->id }}]" :label="$field->label" :type="$field->type === 'date' ? 'date' : ($field->type === 'number' ? 'number' : 'text')" :value="$fieldValue" :required="$field->is_required" />
                        @endif

                        @error("custom_fields.{$field->id}")
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-sm text-slate-400">No custom fields defined yet.</p>
        @endforelse
    </x-admin-card>

    <x-admin-card title="Media">
        <div class="space-y-4">
            {{-- Preview newly selected files instantly, before the form is saved. --}}
            <div x-data="{ previews: [] }"
                 @change="previews.forEach((url) => URL.revokeObjectURL(url));
                          previews = Array.from($event.target.files ?? [])
                              .filter((file) => file.type.startsWith('image/'))
                              .map((file) => URL.createObjectURL(file))">
                <x-admin.form.input name="images[]" label="Upload Images" type="file" accept="image/jpeg,image/png,image/webp" multiple />

                <div x-cloak x-show="previews.length" class="mt-3">
                    <p class="mb-2 text-xs font-medium text-slate-400">New images — saved when you submit</p>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                        <template x-for="src in previews" :key="src">
                            <img :src="src" alt="" class="aspect-video rounded-lg object-cover bg-slate-100 ring-2 ring-brand/40 dark:bg-night-800">
                        </template>
                    </div>
                </div>
            </div>

            @if($listing->exists && $listing->galleryImages->isNotEmpty())
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                    @foreach($listing->galleryImages as $image)
                        <div class="group relative">
                            <img src="{{ $image->url() }}" alt="" class="aspect-video w-full rounded-lg object-cover bg-slate-100 dark:bg-night-800">
                            <button type="button"
                                    data-media-delete="{{ route('admin.listings.images.destroy', [$listing, $image]) }}"
                                    class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-red-600 opacity-0 shadow ring-1 ring-slate-200 transition hover:bg-red-600 hover:text-white focus:opacity-100 group-hover:opacity-100"
                                    aria-label="Delete image" title="Delete image">
                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5'])
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div x-data="{ previews: [] }"
                 @change="previews.forEach((url) => URL.revokeObjectURL(url));
                          previews = Array.from($event.target.files ?? [])
                              .filter((file) => file.type.startsWith('image/'))
                              .map((file) => URL.createObjectURL(file))">
                <x-admin.form.input name="floor_plans[]" label="Upload Floor Plans" type="file" accept="image/jpeg,image/png,image/webp" multiple />

                <div x-cloak x-show="previews.length" class="mt-3">
                    <p class="mb-2 text-xs font-medium text-slate-400">New floor plans — saved when you submit</p>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                        <template x-for="src in previews" :key="src">
                            <img :src="src" alt="" class="aspect-video rounded-lg bg-slate-100 object-contain p-1 ring-2 ring-brand/40 dark:bg-night-800">
                        </template>
                    </div>
                </div>
            </div>

            @if($listing->exists && $listing->floorPlanImages->isNotEmpty())
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                    @foreach($listing->floorPlanImages as $floorPlan)
                        <div class="group relative">
                            <img src="{{ $floorPlan->url() }}" alt="" class="aspect-video w-full rounded-lg bg-slate-100 object-contain p-1 dark:bg-night-800">
                            <button type="button"
                                    data-media-delete="{{ route('admin.listings.images.destroy', [$listing, $floorPlan]) }}"
                                    class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-red-600 opacity-0 shadow ring-1 ring-slate-200 transition hover:bg-red-600 hover:text-white focus:opacity-100 group-hover:opacity-100"
                                    aria-label="Delete floor plan" title="Delete floor plan">
                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5'])
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @for($i = 0; $i < 3; $i++)
                    <x-admin.form.input name="videos[{{ $i }}][url]" label="Video URL {{ $i + 1 }}"
                                        placeholder="YouTube, Vimeo or MP4 link" :value="old("videos.{$i}.url")" />
                @endfor
            </div>

            <div x-data="{ files: [] }"
                 @change="files = Array.from($event.target.files ?? [])
                     .map((file) => file.name + ' (' + (file.size / 1048576).toFixed(1) + ' MB)')">
                <x-admin.form.input name="video_files[]" label="Upload Video Files" type="file" accept="video/mp4,video/webm,video/quicktime" multiple />

                <ul x-cloak x-show="files.length" class="mt-2 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                    <template x-for="name in files" :key="name">
                        <li class="break-all" x-text="'New video — saved when you submit: ' + name"></li>
                    </template>
                </ul>
            </div>

            @if($listing->exists && $listing->videos->isNotEmpty())
                <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 text-sm text-slate-500 dark:divide-night-700 dark:border-night-700 dark:text-slate-400">
                    @foreach($listing->videos as $video)
                        <li class="flex items-center gap-3 px-3 py-2">
                            @if($video->path)
                                <video controls preload="metadata" class="aspect-video w-36 shrink-0 rounded-md bg-black" src="{{ $video->resolvedUrl() }}"></video>
                            @endif
                            <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-night-800 dark:text-slate-400">{{ $video->source }}</span>
                            <a href="{{ $video->resolvedUrl() }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate hover:text-brand hover:underline">{{ $video->resolvedUrl() }}</a>
                            <button type="button"
                                    data-media-delete="{{ route('admin.listings.videos.destroy', [$listing, $video]) }}"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-red-500 transition hover:bg-red-50 dark:hover:bg-red-500/10"
                                    aria-label="Delete video" title="Delete video">
                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5'])
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-admin-card>

    <x-admin-card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $listing->is_featured)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                    Featured
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $listing->is_verified)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                    Verified
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ $listing->exists ? route('admin.listings.show', $listing) : route('admin.listings.index') }}"
                   class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </x-admin-card>
</form>

<script>
    document.querySelector('[name="type"]')?.addEventListener('change', function (event) {
        document.querySelectorAll('[data-custom-field-group]').forEach(function (group) {
            group.classList.toggle('hidden', group.dataset.customFieldGroup !== event.target.value);
        });
    });

    // Instant delete for existing listing media (images, floor plans, videos).
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-media-delete]');
        if (!button) return;

        const confirmed = window.adminConfirm
            ? (await window.adminConfirm({
                  title: 'Delete this file?',
                  text: 'It will be removed permanently. This cannot be undone.',
                  confirmText: 'Yes, delete it',
              })).isConfirmed
            : confirm('Delete this file permanently?');

        if (!confirmed) return;

        button.disabled = true;

        try {
            const response = await fetch(button.dataset.mediaDelete, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]')?.value ?? '',
                },
            });

            if (!response.ok) throw new Error('Delete failed');

            (button.closest('li') ?? button.closest('.group'))?.remove();
            window.adminToast?.('success', 'File deleted.');
        } catch (error) {
            button.disabled = false;
            window.adminToast ? window.adminToast('error', 'Could not delete the file. Please try again.') : alert('Could not delete the file. Please try again.');
        }
    });
</script>
