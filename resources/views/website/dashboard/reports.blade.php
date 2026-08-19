@extends('website.layouts.dashboard')
@section('title', __('My Reports').' | '.config('app.name'))
@section('page-title', __('Reports & Disputes'))
@section('dashboard-content')
    <div class="mx-auto max-w-5xl space-y-8">
        <section>
            <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-slate-950">{{ __('My reports') }}</h2><span class="text-sm text-slate-500">{{ $reports->total() }}</span></div>
            <div class="space-y-3">
                @forelse ($reports as $report)
                    <article class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center">
                        <div>
                            <p class="font-semibold text-slate-900">{{ __(ucfirst($report->reason)) }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ class_basename($report->reportable_type) }} &middot; {{ $report->created_at->format('M j, Y') }}</p>
                            @if ($report->details)<p class="mt-2 text-xs text-slate-500">{{ $report->details }}</p>@endif
                        </div>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $report->status === 'actioned' ? 'bg-emerald-50 text-emerald-700' : ($report->status === 'dismissed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') }}">{{ __(ucfirst($report->status)) }}</span>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">{{ __('No reports submitted yet.') }}</div>
                @endforelse
            </div>
            @if ($reports->hasPages())<div class="mt-4">{{ $reports->links() }}</div>@endif
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-slate-950">{{ __('My disputes') }}</h2><span class="text-sm text-slate-500">{{ $disputes->total() }}</span></div>
            <div class="space-y-3">
                @forelse ($disputes as $dispute)
                    <article class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $dispute->subject }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $dispute->listing?->title }} &middot; {{ $dispute->created_at->format('M j, Y') }}</p>
                            @if ($dispute->resolution)<p class="mt-2 text-xs text-slate-500">{{ __('Resolution:') }} {{ $dispute->resolution }}</p>@endif
                        </div>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $dispute->status === 'resolved' ? 'bg-emerald-50 text-emerald-700' : ($dispute->status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') }}">{{ __(ucfirst($dispute->status)) }}</span>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">{{ __('No disputes opened yet.') }}</div>
                @endforelse
            </div>
            @if ($disputes->hasPages())<div class="mt-4">{{ $disputes->links() }}</div>@endif
        </section>
    </div>
@endsection
