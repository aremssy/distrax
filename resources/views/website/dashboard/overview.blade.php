@extends('website.layouts.dashboard')

@section('title', __('Overview').' | Rentdo')
@section('page-title', __('Overview'))

@section('dashboard-content')
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-950">{{ __('Welcome back, :name', ['name' => str(auth()->user()->name)->before(' ')]) }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ __("Here's what's happening with your account today.") }}</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach ($stats as $stat)
                <a href="{{ route($stat['route']) }}"
                    class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md hover:border-slate-300 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center mb-3">
                        <i class="w-5 h-5 text-slate-600" data-lucide="{{ $stat['icon'] }}"></i>
                    </div>
                    <div class="text-2xl font-bold text-slate-950">{{ $stat['value'] }}</div>
                    <div class="text-sm text-slate-500 mt-0.5">{{ $stat['label'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main column --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="font-semibold text-slate-950">{{ __('Upcoming Visits') }}</h3>
                        <a href="{{ route('dashboard.activity') }}"
                            class="text-sm text-slate-500 font-medium hover:text-slate-950">{{ __('View All') }}</a>
                    </div>
                    <div class="space-y-1">
                        @forelse ($upcomingVisits->take(3) as $visit)
                            @php
                                $cover = $visit->listing?->coverImage->first();
                                $coverUrl = match (true) {
                                    ! $cover => asset('assets/images/website/features/flat-1.webp'),
                                    $cover->disk === 'remote' || \Illuminate\Support\Str::startsWith($cover->path, ['http://', 'https://']) => $cover->path,
                                    default => \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path),
                                };
                            @endphp
                            <a href="{{ route('properties.show', $visit->listing) }}"
                                class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition">
                                <img src="{{ $coverUrl }}"
                                    class="w-16 h-16 rounded-lg object-cover shrink-0" alt="{{ $visit->listing->title }}">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-950 text-sm truncate">{{ $visit->listing->title }}</p>
                                    <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5">
                                        <i class="w-3.5 h-3.5" data-lucide="clock"></i> {{ $visit->scheduled_at->format('M j, Y · g:i A') }}
                                    </p>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0 {{ $visit->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ __(ucfirst($visit->status)) }}</span>
                            </a>
                        @empty
                            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                <i class="h-6 w-6 text-slate-400" data-lucide="calendar-check"></i>
                                <p class="text-sm text-slate-500">{{ __('No upcoming visits scheduled.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-6">
                    <h3 class="font-semibold text-slate-950 mb-5">{{ __('Recent Activity') }}</h3>
                    <div class="space-y-5">
                        @forelse ($recentNotifications as $notification)
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="w-4 h-4 text-slate-500" data-lucide="bell"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-700 leading-snug">{{ $notification->title }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                <i class="h-6 w-6 text-slate-400" data-lucide="bell"></i>
                                <p class="text-sm text-slate-500">{{ __('No recent activity yet.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="bg-linear-to-br from-indigo-600 to-indigo-700 rounded-2xl p-6 text-white shadow-sm">
                    <div class="flex items-center gap-2 text-sm font-medium text-indigo-100 mb-1">
                        <i class="w-4 h-4" data-lucide="sparkles"></i> {{ __('Free Plan') }}
                    </div>
                    <p class="text-sm text-indigo-100 leading-relaxed mb-4">{{ __('Upgrade to unlock rent reminders, income tracking, and maintenance requests.') }}</p>
                    <a href="{{ route('dashboard.wallet') }}"
                        class="block text-center bg-white text-indigo-700 text-sm font-semibold py-2.5 rounded-lg hover:bg-indigo-50 transition">{{ __('View Plans') }}</a>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-6">
                    <h3 class="font-semibold text-slate-950 mb-1">{{ __('Complete your profile') }}</h3>
                    <p class="text-sm text-slate-500 mb-4">{{ __('A verified profile builds trust with owners.') }}</p>
                    <div class="h-1.5 rounded-full bg-slate-100 mb-2">
                        <div class="h-full w-3/4 bg-indigo-600 rounded-full"></div>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">{{ __(':percent% complete', ['percent' => 75]) }}</p>
                    <a href="{{ route('dashboard.profile') }}"
                        class="flex items-center justify-center gap-1.5 text-sm font-semibold text-slate-950 border border-slate-200 py-2.5 rounded-lg hover:bg-slate-50 transition">
                        {{ __('Finish Setup') }} <i class="w-3.5 h-3.5" data-lucide="arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
