@php($set = $set ?? false)
{{-- A credential field that never echoes the stored secret. When a value already
     exists ($set), it shows a "Configured" badge and keeps it if left blank.
     Included with: @include('admin.settings.partials.secret', ['name' => ..., 'label' => ..., 'set' => bool]) --}}
<div x-data="{ show: false }">
    <label for="{{ $name }}" class="mb-1.5 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
        {{ $label }}
        @if ($set)
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-3 h-3'])
                Configured
            </span>
        @endif
    </label>
    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            value=""
            :type="show ? 'text' : 'password'"
            autocomplete="new-password"
            data-1p-ignore
            data-lpignore="true"
            placeholder="{{ $set ? '•••••••• (leave blank to keep current)' : 'Not set' }}"
            @class([
                'block w-full rounded-xl border px-3.5 py-2.5 pr-10 text-sm transition focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand/50',
                'border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-900/20 dark:border-rose-600 dark:text-rose-200' => $errors->has($name),
                'border-slate-200 bg-white text-slate-800 placeholder-slate-400 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500' => ! $errors->has($name),
            ])
        >
        <button type="button" @click="show = !show"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                :aria-label="show ? 'Hide {{ $label }}' : 'Show {{ $label }}'">
            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
        </button>
    </div>
    @error($name)
        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>
