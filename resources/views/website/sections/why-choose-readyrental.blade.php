@php
    // Icon/tone are structural; title & description are translatable via __().
    $benefits = [
        ['icon' => 'shield-check', 'title' => 'Verified Listings', 'desc' => 'All listings are verified for your safety.', 'tone' => 'green'],
        ['icon' => 'users', 'title' => 'Trusted Agents', 'desc' => 'Work with professional and trusted agents.', 'tone' => 'blue'],
        ['icon' => 'lock', 'title' => 'Secure Transactions', 'desc' => 'We ensure secure and transparent transactions.', 'tone' => 'slate'],
        ['icon' => 'search', 'title' => 'Easy Property Search', 'desc' => 'Advanced filters to find the perfect property.', 'tone' => 'blue'],
        ['icon' => 'calendar', 'title' => 'Schedule Visits', 'desc' => 'Book property visits online with ease.', 'tone' => 'green'],
        ['icon' => 'smartphone', 'title' => 'Mobile Access', 'desc' => 'Access everything on the go.', 'tone' => 'slate'],
    ];
@endphp

<section class="mx-auto max-w-7xl px-4 py-14 sm:py-20 md:px-6" data-purpose="why-choose-us" data-reveal>
    <div class="mb-10 text-center sm:mb-16" data-reveal-item>
        <h2 class="mb-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ __('Why Choose Rentdo?') }}</h2>
        <div class="w-16 h-1 bg-indigo-600 mx-auto rounded"></div>
    </div>

    <div class="grid grid-cols-1 gap-8 min-[360px]:grid-cols-2 lg:grid-cols-6" data-reveal-children>
        @foreach ($benefits as $benefit)
            <div class="flex flex-col items-center text-center">
                <div @class([
                    'w-16 h-16 rounded-2xl flex items-center justify-center mb-4 border',
                    'bg-green-50 text-emerald-500 border-green-100' => $benefit['tone'] === 'green',
                    'bg-blue-50 text-indigo-600 border-blue-100' => $benefit['tone'] === 'blue',
                    'bg-slate-50 text-slate-600 border-slate-100' => $benefit['tone'] === 'slate',
                ])>
                    <i class="w-8 h-8" data-lucide="{{ $benefit['icon'] }}"></i>
                </div>
                <h3 class="mb-2 text-base font-bold text-slate-950">{{ __($benefit['title']) }}</h3>
                <p class="text-sm leading-relaxed text-slate-600">{{ __($benefit['desc']) }}</p>
            </div>
        @endforeach
    </div>
</section>
