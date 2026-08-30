@extends('website.layouts.master')

@section('title', __('Inspection').' | '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('inspections.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back to inspections') }}
        </a>

        @if (session('success'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-950">{{ $inspection->listing->title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $inspection->listing->address }}</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600"><i class="h-3.5 w-3.5" data-lucide="eye"></i>{{ __(ucfirst($inspection->type)) }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-bold uppercase tracking-wide
                            @if ($inspection->status === 'completed') bg-emerald-100 text-emerald-700
                            @elseif ($inspection->status === 'cancelled') bg-slate-100 text-slate-600
                            @else bg-indigo-100 text-indigo-700 @endif">{{ __(ucfirst($inspection->status)) }}</span>
                        @if ($inspection->buyer_acknowledged_at)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700"><i class="h-3.5 w-3.5" data-lucide="badge-check"></i>{{ __('Acknowledged') }}</span>
                        @endif
                    </div>
                </div>
                @if ($inspection->scheduled_at)
                    <div class="text-end text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Scheduled') }}</p>
                        <p class="mt-0.5 font-semibold text-slate-800">{{ $inspection->scheduled_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                @if ($inspection->checklist)
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h2 class="text-base font-bold text-slate-950">{{ __('Inspection checklist') }}</h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach ($inspection->checklist as $item)
                                <div class="flex items-center justify-between gap-3 px-6 py-3">
                                    <span class="text-sm text-slate-700">{{ $item['label'] ?? $item }}</span>
                                    @if (isset($item['passed']))
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $item['passed'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $item['passed'] ? __('Pass') : __('Fail') }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($inspection->summary || $inspection->issues)
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h2 class="text-base font-bold text-slate-950">{{ __('Inspector report') }}</h2>
                        </div>
                        <div class="space-y-4 p-6 text-sm">
                            @if ($inspection->summary)
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Summary') }}</p>
                                    <p class="mt-1 text-slate-700">{{ $inspection->summary }}</p>
                                </div>
                            @endif
                            @if ($inspection->issues)
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-500">{{ __('Issues found') }}</p>
                                    <p class="mt-1 text-rose-700">{{ $inspection->issues }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($inspection->evidence->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h2 class="text-base font-bold text-slate-950">{{ __('Evidence') }}</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-3 p-6 sm:grid-cols-3">
                            @foreach ($inspection->evidence as $evidence)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('local')->url($evidence->file_path) }}" target="_blank" rel="noopener"
                                    class="flex aspect-square items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-indigo-400">
                                    <i class="h-6 w-6" data-lucide="{{ $evidence->type === 'photo' ? 'image' : 'file-text' }}"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($inspection->report_url)
                    <a href="{{ $inspection->report_url }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                        <i class="h-4 w-4" data-lucide="file-down" aria-hidden="true"></i>{{ __('Download full report') }}
                    </a>
                @endif
            </div>

            <div class="space-y-4">
                @if ($inspection->status === 'scheduled' && auth()->id() === $inspection->booked_by)
                    <form method="POST" action="{{ route('inspections.cancel', $inspection) }}" class="rounded-2xl border border-slate-200 bg-white p-5"
                        onsubmit="return confirm('{{ __('Cancel this inspection?') }}')">
                        @csrf
                        <p class="text-sm font-semibold text-slate-900">{{ __('Change of plans?') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('You can cancel a scheduled inspection before an inspector is assigned.') }}</p>
                        <button type="submit" class="mt-3 w-full rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('Cancel inspection') }}</button>
                    </form>
                @endif

                @if ($inspection->status === 'completed' && auth()->id() === $inspection->booked_by && ! $inspection->buyer_acknowledged_at)
                    <form method="POST" action="{{ route('inspections.acknowledge', $inspection) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        @csrf
                        <p class="text-sm font-semibold text-emerald-900">{{ __('Review the report') }}</p>
                        <p class="mt-1 text-xs text-emerald-700">{{ __('Acknowledge once you have reviewed the findings. This satisfies the inspection condition on your offer.') }}</p>
                        <button type="submit" class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">{{ __('Acknowledge report') }}</button>
                    </form>
                @endif

                @if ($inspection->status === 'completed' && $inspection->buyer_acknowledged_at)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
                        <i class="mx-auto h-8 w-8 text-emerald-500" data-lucide="badge-check"></i>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('Report acknowledged') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $inspection->buyer_acknowledged_at->diffForHumans() }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
