{{--
    Country dial-code + phone number input.
    Renders a flag/dial-code <select> glued to a number field and submits a single
    combined E.164-ish value under {{ $name }} (e.g. "+8801711223344"), so no
    server-side changes are needed on the forms that use it.
--}}
<div
    x-data="{
        code: @js($dialCode),
        number: @js($localNumber),
        get combined() { const n = this.number.replace(/\D/g, ''); return n ? '+' + this.code + n : '' },
    }"
    {{ $attributes->merge(['class' => 'block']) }}
>
    <label class="text-sm font-semibold text-slate-700" for="{{ $id }}">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-slate-500">(optional)</span>
        @endif
    </label>

    <div class="mt-2 flex h-12 overflow-hidden rounded-xl border border-slate-200 bg-white transition focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 hover:border-slate-300">
        <div class="relative h-full shrink-0 border-r border-slate-200">
            <select
                x-model="code"
                aria-label="Country dial code"
                class="h-full cursor-pointer appearance-none bg-slate-50 pl-2.5 pr-6 text-sm text-slate-700 outline-none focus:ring-0"
            >
                @foreach ($countries as $country)
                    <option value="{{ $country['phone_code'] }}" title="{{ $country['name'] }}">
                        {{ $country['flag'] }} +{{ $country['phone_code'] }}
                    </option>
                @endforeach
            </select>
            <svg class="pointer-events-none absolute right-1.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </div>

        <input
            id="{{ $id }}"
            type="tel"
            inputmode="numeric"
            autocomplete="tel"
            x-model="number"
            @if ($required) required @endif
            placeholder="{{ $placeholder }}"
            class="h-full w-full border-0 bg-white px-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0"
        >
    </div>

    {{-- Combined value actually submitted with the form. --}}
    <input type="hidden" name="{{ $name }}" :value="combined">

    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
