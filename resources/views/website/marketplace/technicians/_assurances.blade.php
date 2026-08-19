@php
    $assurances = [
        ['icon' => 'shield-check', 'tone' => 'bg-indigo-50 text-indigo-600', 'title' => __('Verified Professionals'), 'text' => __('All technicians are background checked and verified.')],
        ['icon' => 'clock', 'tone' => 'bg-emerald-50 text-emerald-600', 'title' => __('On-time Service'), 'text' => __('We value your time and ensure punctual service.')],
        ['icon' => 'sparkles', 'tone' => 'bg-amber-50 text-amber-600', 'title' => __('Quality Work'), 'text' => __('High quality service with customer satisfaction.')],
        ['icon' => 'headphones', 'tone' => 'bg-sky-50 text-sky-600', 'title' => __('24/7 Support'), 'text' => __("We're here to help you anytime, anywhere.")],
    ];
@endphp

<section class="mt-8 grid grid-cols-1 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 sm:divide-x sm:divide-slate-100 lg:grid-cols-4 rtl:sm:divide-x-reverse"
    aria-label="{{ __('Why book through us') }}" data-purpose="technician-assurances">
    @foreach ($assurances as $assurance)
        <div class="flex items-start gap-3 px-0 py-4 sm:px-6 sm:py-2">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $assurance['tone'] }}">
                <i class="h-5 w-5" data-lucide="{{ $assurance['icon'] }}" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-slate-950">{{ $assurance['title'] }}</h2>
                <p class="mt-1 text-[13px] leading-relaxed text-slate-500">{{ $assurance['text'] }}</p>
            </div>
        </div>
    @endforeach
</section>
