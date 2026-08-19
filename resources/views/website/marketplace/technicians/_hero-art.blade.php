{{-- Decorative hero art: city skyline with a technician holding a wrench and a trust shield. --}}
<svg class="h-full w-full" viewBox="0 0 420 140" fill="none" preserveAspectRatio="xMaxYMax meet"
    xmlns="http://www.w3.org/2000/svg" role="presentation" focusable="false">
    {{-- Skyline (sits behind everything) --}}
    <g class="fill-indigo-100">
        <rect x="20" y="62" width="40" height="78" rx="3" />
        <rect x="68" y="88" width="30" height="52" rx="3" />
        <rect x="106" y="50" width="28" height="90" rx="3" />
        <rect x="142" y="96" width="26" height="44" rx="3" />
        <rect x="296" y="46" width="34" height="94" rx="3" />
        <rect x="338" y="80" width="28" height="60" rx="3" />
        <rect x="372" y="104" width="30" height="36" rx="3" />
    </g>
    <g class="fill-indigo-200/60">
        <rect x="34" y="76" width="7" height="7" rx="2" />
        <rect x="34" y="92" width="7" height="7" rx="2" />
        <rect x="115" y="64" width="7" height="7" rx="2" />
        <rect x="115" y="80" width="7" height="7" rx="2" />
        <rect x="306" y="60" width="7" height="7" rx="2" />
        <rect x="306" y="76" width="7" height="7" rx="2" />
        <rect x="346" y="94" width="7" height="7" rx="2" />
        <rect x="117" y="34" width="5" height="18" rx="2.5" />
    </g>

    {{-- Trust shield --}}
    <path d="M378 16l24 9v19c0 16-9 29-24 34-15-5-24-18-24-34V25l24-9Z" class="fill-indigo-400" />
    <path d="M369 46l7 7 14-15" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"
        class="text-white" />

    {{-- Technician --}}
    <g>
        {{-- Torso --}}
        <path d="M194 140v-24a36 36 0 0 1 72 0v24Z" class="fill-indigo-600" />
        {{-- Bib --}}
        <path d="M214 140v-36a16 16 0 0 1 32 0v36Z" class="fill-indigo-500" />
        {{-- Neck --}}
        <rect x="221" y="66" width="18" height="20" rx="8" class="fill-amber-200" />
        {{-- Head --}}
        <circle cx="230" cy="50" r="22" class="fill-amber-200" />
        <circle cx="209" cy="52" r="4.5" class="fill-amber-200" />
        {{-- Cap --}}
        <path d="M209 40a21 21 0 0 1 42 0Z" class="fill-indigo-600" />
        <rect x="205" y="37" width="50" height="8" rx="4" class="fill-indigo-700" />
        <path d="M255 38h20a4 4 0 0 1 0 8h-20Z" class="fill-indigo-700" />
        {{-- Face --}}
        <circle cx="223" cy="52" r="2.4" class="fill-slate-800" />
        <circle cx="239" cy="52" r="2.4" class="fill-slate-800" />
        <path d="M224 61a9 9 0 0 0 13 0" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
            class="text-slate-700" />
        {{-- Crossed arms --}}
        <rect x="190" y="100" width="80" height="17" rx="8.5" class="fill-indigo-600" />
        <circle cx="196" cy="108" r="9" class="fill-amber-200" />

        {{-- Wrench, held in the right hand --}}
        <g transform="rotate(-38 274 92)">
            <rect x="268" y="60" width="11" height="46" rx="5.5" class="fill-slate-400" />
            <circle cx="273.5" cy="56" r="12" class="fill-slate-400" />
            <path d="M273.5 44a12 12 0 0 0-8 21l4-6a6 6 0 0 1 4-11Z" class="fill-slate-50" />
        </g>
        <circle cx="266" cy="110" r="9" class="fill-amber-200" />
    </g>
</svg>
