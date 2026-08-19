@props(['selected' => []])

{{-- Audience segments the campaign will be sent to. Keys come from Campaign::SEGMENTS. --}}
<div>
    <span class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Target Segments</span>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        @foreach (\App\Models\Campaign::SEGMENTS as $key => $label)
            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 transition hover:border-brand/50 dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <input type="checkbox" name="target_segments[]" value="{{ $key }}"
                    @checked(in_array($key, old('target_segments', $selected), true))
                    class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand dark:border-night-600 dark:bg-night-900">
                {{ $label }}
            </label>
        @endforeach
    </div>
    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Leave all unchecked to send to every user.</p>
    @error('target_segments')
        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
