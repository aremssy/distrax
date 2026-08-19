@extends('website.layouts.dashboard')
@section('title', __('Maintenance Jobs').' | '.config('app.name'))
@section('page-title', __('Maintenance Jobs'))
@section('dashboard-content')
    @php
        $statusStyles = [
            'assigned' => 'bg-sky-50 text-sky-700',
            'in_progress' => 'bg-indigo-50 text-indigo-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'closed' => 'bg-slate-100 text-slate-600',
        ];
    @endphp
    <div class="mx-auto max-w-5xl space-y-3">
        @forelse ($requests as $request)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $request->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $request->listing?->title ?? __('Listing removed') }} &middot; {{ __('Owner:') }} {{ $request->owner?->name }}</p>
                        @if ($request->description)<p class="mt-2 text-sm text-slate-600">{{ $request->description }}</p>@endif
                        <p class="mt-2 text-xs text-slate-400">{{ __('Priority:') }} {{ __(ucfirst($request->priority)) }} &middot; {{ $request->created_at->format('M j, Y') }}</p>
                    </div>
                    <span class="w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$request->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($request->status)->replace('_', ' ')->title() }}</span>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    @if ($request->status === 'assigned')
                        <form action="{{ route('dashboard.maintenance.update', $request) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="in_progress">
                            <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white">{{ __('Start job') }}</button>
                        </form>
                    @elseif ($request->status === 'in_progress')
                        <form action="{{ route('dashboard.maintenance.update', $request) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">{{ __('Mark completed') }}</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">{{ __('No maintenance jobs assigned to you yet.') }}</div>
        @endforelse
        @if ($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
    </div>
@endsection
