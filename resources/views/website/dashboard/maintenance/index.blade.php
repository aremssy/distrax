@extends('website.layouts.dashboard')
@section('title', __('Maintenance Requests').' | '.config('app.name'))
@section('page-title', __('Maintenance Requests'))
@section('dashboard-content')
    @php
        $statusStyles = [
            'open' => 'bg-amber-50 text-amber-700',
            'assigned' => 'bg-sky-50 text-sky-700',
            'in_progress' => 'bg-indigo-50 text-indigo-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'closed' => 'bg-slate-100 text-slate-600',
        ];
    @endphp
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-semibold">
                <a href="{{ route('dashboard.maintenance.index', ['role' => 'tenant']) }}"
                    class="rounded-md px-3 py-1.5 transition {{ $role === 'tenant' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500' }}">{{ __('As tenant') }}</a>
                <a href="{{ route('dashboard.maintenance.index', ['role' => 'owner']) }}"
                    class="rounded-md px-3 py-1.5 transition {{ $role === 'owner' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500' }}">{{ __('As owner') }}</a>
            </nav>
            @if ($role === 'tenant')
                <a href="{{ route('dashboard.maintenance.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('New request') }}</a>
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($requests as $request)
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $request->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $request->listing?->title ?? __('Listing removed') }}</p>
                            @if ($request->description)<p class="mt-2 text-sm text-slate-600">{{ $request->description }}</p>@endif
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __('Priority:') }} {{ __(ucfirst($request->priority)) }}
                                @if ($request->technician)&middot; {{ __('Technician:') }} {{ $request->technician->user?->name }}@endif
                                &middot; {{ $request->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <span class="w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$request->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($request->status)->replace('_', ' ')->title() }}</span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                        @if ($role === 'owner' && $request->status === 'open')
                            <form action="{{ route('dashboard.maintenance.update', $request) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="assigned">
                                <label class="sr-only" for="technician-{{ $request->id }}">{{ __('Assign technician') }}</label>
                                <select id="technician-{{ $request->id }}" name="technician_id" required
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
                                    <option value="">{{ __('Choose a technician…') }}</option>
                                    @foreach ($technicians as $technician)
                                        <option value="{{ $technician->id }}">{{ $technician->user?->name }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white">{{ __('Assign') }}</button>
                            </form>
                        @endif

                        @if ($role === 'tenant' && $request->status === 'completed')
                            <form action="{{ route('dashboard.maintenance.update', $request) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="closed">
                                <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ __('Confirm & close') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
                    {{ $role === 'owner' ? __('No maintenance requests for your listings.') : __('No maintenance requests yet.') }}
                </div>
            @endforelse
        </div>
        @if ($requests->hasPages())<div>{{ $requests->appends(['role' => $role])->links() }}</div>@endif
    </div>
@endsection
