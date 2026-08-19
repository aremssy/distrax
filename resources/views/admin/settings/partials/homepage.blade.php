{{-- Translatable homepage / footer content editor.
     Writes to the `translations` table (group "*") for the selected language,
     keyed by the English sentence itself; blank fields fall back to that
     English default baked into the view/controller. --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Edit the text shown on the public homepage and footer. Leave a field blank to use the default English text.
        </p>
    </div>

    @if ($homeLanguages->count() > 1)
        <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-night-700 dark:bg-night-800">
            @foreach ($homeLanguages as $language)
                <a href="{{ route('admin.settings.index', ['tab' => 'homepage', 'lang' => $language->id]) }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition
                       {{ (int) $homeLocaleId === $language->id
                           ? 'bg-white text-brand shadow-sm dark:bg-night-900 dark:text-indigo-400'
                           : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    {{ $language->name }}
                </a>
            @endforeach
        </div>
    @endif
</div>

<div class="space-y-6">
    @foreach ($homeGroups as $groupLabel => $fields)
        @php $sectionImages = \App\Support\HomeContent::imagesFor($groupLabel); @endphp
        {{-- Each section is its own form so it can be saved independently. --}}
        <form method="POST" action="{{ route('admin.settings.homepage') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="language_id" value="{{ $homeLocaleId }}">

            <x-admin-card :title="$groupLabel">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach ($fields as $key => $meta)
                        @php
                            $default = $meta['text'];
                            // Pre-fill with the saved override, or the English default so the
                            // admin always sees the live text. Saving a value equal to the
                            // default stores nothing (keeps the fallback).
                            $current = old('content.'.$key, $homeValues[$key] ?? $default);
                            $isTextarea = ($meta['type'] ?? 'text') === 'textarea';
                        @endphp
                        <div class="{{ $isTextarea ? 'md:col-span-2' : '' }}">
                            @if ($isTextarea)
                                <x-admin.form.textarea :name="'content['.$key.']'" :label="$meta['label']" :rows="2"
                                    :placeholder="$default">{{ $current }}</x-admin.form.textarea>
                            @else
                                <x-admin.form.input :name="'content['.$key.']'" :label="$meta['label']"
                                    :value="$current" :placeholder="$default" />
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (! empty($sectionImages))
                    <div class="mt-6 border-t border-slate-100 pt-6 dark:border-night-700">
                        <p class="mb-4 text-sm font-semibold text-slate-700 dark:text-slate-200">Images</p>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($sectionImages as $imageKey => $imageMeta)
                                @php $customised = filled(setting($imageKey)); @endphp
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-night-700">
                                    <div class="mb-3 flex h-28 items-center justify-center overflow-hidden rounded-lg border border-slate-100 bg-slate-50 dark:border-night-700 dark:bg-night-800">
                                        <img src="{{ home_image($imageKey, $imageMeta['default']) }}" alt="{{ $imageMeta['label'] }}" class="max-h-full max-w-full object-contain">
                                    </div>
                                    <label for="{{ $imageKey }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $imageMeta['label'] }}</label>
                                    <input id="{{ $imageKey }}" type="file" name="images[{{ $imageKey }}]" accept="image/png,image/jpeg,image/webp"
                                        class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                                    @error('images.'.$imageKey)
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                    @enderror
                                    @if ($customised)
                                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <input type="checkbox" name="images_remove[{{ $imageKey }}]" value="1"
                                                class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            Reset to default
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                        Save {{ $groupLabel }}
                    </button>
                </div>
            </x-admin-card>
        </form>
    @endforeach
</div>
