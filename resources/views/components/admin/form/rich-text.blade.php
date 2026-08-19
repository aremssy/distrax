@props([
    'label' => null,
    'name',
    'hint' => null,
    'required' => false,
    'error' => null,
    'rows' => 10,
])

@php
    $error ??= $errors->first($name);
    $fieldId = 'rte-'.$name.'-'.uniqid();
@endphp

@once
    @push('head')
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
        <style>
            /* The editor mount is hidden until JS enhances the field, so no-JS falls back
               to the plain textarea below it. */
            [data-rich-text] { display: none; }
            .rte-shell .ql-toolbar { border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; border-color: rgb(226 232 240); background: rgb(248 250 252); }
            .rte-shell .ql-container { border-bottom-left-radius: 0.75rem; border-bottom-right-radius: 0.75rem; border-color: rgb(226 232 240); font-family: inherit; }
            .rte-shell .ql-editor { min-height: 12rem; font-size: 0.9rem; }
            .dark .rte-shell .ql-toolbar { background: rgb(30 41 59); border-color: rgb(51 65 85); }
            .dark .rte-shell .ql-container { border-color: rgb(51 65 85); background: rgb(15 23 42); }
            .dark .rte-shell .ql-editor { color: rgb(226 232 240); }
            .dark .rte-shell .ql-snow .ql-stroke { stroke: rgb(148 163 184); }
            .dark .rte-shell .ql-snow .ql-fill { fill: rgb(148 163 184); }
            .dark .rte-shell .ql-snow .ql-picker { color: rgb(148 163 184); }
            .rte-shell.rte-invalid .ql-toolbar, .rte-shell.rte-invalid .ql-container { border-color: rgb(251 113 133); }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
        <script data-purpose="rich-text-init">
            window.initRichTextEditors = function () {
                if (typeof Quill === 'undefined') return;

                document.querySelectorAll('[data-rich-text]:not([data-rte-ready])').forEach(function (mount) {
                    const textarea = document.getElementById(mount.dataset.richText);
                    if (!textarea) return;

                    mount.setAttribute('data-rte-ready', 'true');
                    mount.style.display = 'block';
                    textarea.style.display = 'none';

                    const editor = new Quill(mount, {
                        theme: 'snow',
                        placeholder: mount.dataset.placeholder || 'Write here…',
                        modules: {
                            toolbar: [
                                [{ header: [2, 3, 4, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['blockquote', 'link'],
                                ['clean'],
                            ],
                        },
                    });

                    editor.clipboard.dangerouslyPasteHTML(textarea.value || '');

                    // Keep the textarea (the real form value) in sync; blank when empty so
                    // required-validation still fires instead of submitting "<p><br></p>".
                    const sync = function () {
                        textarea.value = editor.getText().trim().length ? editor.root.innerHTML : '';
                    };
                    editor.on('text-change', sync);
                    sync();
                });
            };

            document.addEventListener('DOMContentLoaded', window.initRichTextEditors);
            if (document.readyState !== 'loading') window.initRichTextEditors();
        </script>
    @endpush
@endonce

<div data-purpose="rich-text-field">
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}@if ($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif

    <div class="rte-shell {{ $error ? 'rte-invalid' : '' }}">
        <div data-rich-text="{{ $fieldId }}"
            @if ($attributes->get('placeholder')) data-placeholder="{{ $attributes->get('placeholder') }}" @endif></div>
    </div>

    {{-- Source of truth and no-JS fallback: JS hides this and syncs Quill's HTML into it. --}}
    <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
        class="block w-full resize-y rounded-xl border px-3.5 py-2.5 text-sm transition
            focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand/50
            {{ $error
                ? 'border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-900/20 dark:border-rose-600 dark:text-rose-200'
                : 'border-slate-200 bg-white text-slate-800 placeholder-slate-400 dark:border-night-700 dark:bg-night-800 dark:text-slate-200' }}">{{ $slot }}</textarea>

    @if ($error)
        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
