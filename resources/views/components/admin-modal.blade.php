@props(['id', 'title' => null, 'maxWidth' => 'lg'])
@php
$sizes = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'];
$size = $sizes[$maxWidth] ?? $sizes['lg'];
@endphp
{{-- Shown/hidden purely via the `hidden` class from openModal()/closeModal() below.
     An earlier Alpine `x-show` bound to a non-reactive dataset attribute left an inline
     display:none that openModal() could not clear, so modals never opened. --}}
<div
    id="{{ $id }}"
    class="fixed inset-0 z-50 hidden overflow-y-auto"
    aria-labelledby="{{ $id }}-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeModal('{{ $id }}')"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative max-h-[90dvh] w-full {{ $size }} overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-night-700 dark:bg-night-900">
            {{-- Header --}}
            @if ($title || isset($header))
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-night-800">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h3 id="{{ $id }}-title" class="text-base font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
                    @endisset
                    <button type="button" onclick="closeModal('{{ $id }}')" class="ml-auto rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-5 h-5'])
                    </button>
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @isset($footer)
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-night-800">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            el.dataset.open = 'true';
            el.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            el.dataset.open = 'false';
            el.classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(m => closeModal(m.id));
            }
        });
    </script>
    @endpush
@endonce
