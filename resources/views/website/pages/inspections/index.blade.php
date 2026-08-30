@extends('website.layouts.dashboard')

@section('title', __('Inspections').' | '.config('app.name'))
@section('page-title', __('Inspections'))

@section('dashboard-content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-950">{{ __('Inspections') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Track inspections you have booked or received.') }}</p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
                @forelse ($inspections as $inspection)
                    <a href="{{ route('inspections.show', $inspection) }}"
                        class="group rounded-xl border border-slate-200 p-5 transition hover:border-indigo-400 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 group-hover:text-indigo-600">{{ $inspection->listing->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $inspection->listing->address }}</p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide
                                @if ($inspection->status === 'completed') bg-emerald-100 text-emerald-700
                                @elseif ($inspection->status === 'cancelled') bg-slate-100 text-slate-600
                                @else bg-indigo-100 text-indigo-700 @endif">
                                {{ __(ucfirst($inspection->status)) }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1"><i class="h-3.5 w-3.5" data-lucide="eye"></i>{{ __(':type inspection', ['type' => __(ucfirst($inspection->type))]) }}</span>
                            @if ($inspection->scheduled_at)
                                <span class="inline-flex items-center gap-1"><i class="h-3.5 w-3.5" data-lucide="calendar"></i>{{ $inspection->scheduled_at->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-12 text-center text-sm text-slate-500">{{ __('No inspections yet.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="mt-6">
            {{ $inspections->links() }}
        </div>
    </div>
@endsection
