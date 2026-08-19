<x-admin-layout :title="$agency->name">
    <x-admin-page-header :title="$agency->name"
        :breadcrumbs="[['label' => 'Agencies', 'route' => 'admin.agencies.index'], ['label' => '#'.$agency->id]]">
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                @can('agencies.edit')
                    <a href="{{ route('admin.agencies.edit', $agency) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                        Edit
                    </a>
                @endcan
            </div>
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            <x-admin-card title="Agency Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Name</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $agency->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Status</dt>
                        <dd>
                            @php
                                $sColor = match($agency->status) {
                                    'active' => 'green',
                                    'inactive' => 'gray',
                                    'suspended' => 'red',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin-badge :color="$sColor">{{ ucfirst($agency->status) }}</x-admin-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Phone</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $agency->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Email</dt>
                        <dd class="break-all text-slate-700 sm:break-normal dark:text-slate-200">{{ $agency->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Website</dt>
                        <dd>
                            @if($agency->website)
                                <a href="{{ $agency->website }}" target="_blank"
                                   class="break-all text-indigo-600 hover:underline sm:break-normal dark:text-indigo-400">{{ $agency->website }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Zone</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $agency->zone?->name ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Address</dt>
                        <dd class="break-words text-slate-700 dark:text-slate-200">{{ $agency->address ?? '—' }}</dd>
                    </div>
                </dl>

                @if($agency->description)
                    <div class="mt-5 pt-4 border-t border-slate-100 dark:border-night-700">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-2">Description</p>
                        <p class="whitespace-pre-line break-words text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $agency->description }}</p>
                    </div>
                @endif
            </x-admin-card>

            {{-- Agents --}}
            <x-admin-card title="Agents ({{ $agency->agents->count() }})">
                <div class="space-y-2">
                    @forelse($agency->agents as $agent)
                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm py-1.5 border-b border-slate-100 dark:border-night-700 last:border-0">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                                    {{ strtoupper(substr($agent->user?->name ?? '?', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $agent->user?->name ?? 'Unknown' }}
                                        @unless($agent->is_active)
                                            <x-admin-badge color="gray" class="ml-1">Inactive</x-admin-badge>
                                        @endunless
                                    </p>
                                    <p class="break-all text-xs text-slate-400 sm:break-normal">{{ $agent->user?->email }}</p>
                                </div>
                            </div>

                            @can('agencies.edit')
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('admin.agencies.agents.update', [$agency, $agent]) }}"
                                          class="flex flex-wrap items-center gap-2">
                                        @csrf @method('PUT')
                                        <input type="text" name="title" value="{{ $agent->title }}" placeholder="Title"
                                               class="w-32 px-2 py-1 text-xs border border-slate-200 dark:border-night-700 rounded-xl bg-white dark:bg-night-900 text-slate-700 dark:text-slate-200">
                                        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($agent->is_active)
                                                   class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            Active
                                        </label>
                                        <button type="submit"
                                                class="px-2.5 py-1 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-lg hover:bg-slate-50 dark:hover:bg-night-800 transition">
                                            Save
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.agencies.agents.destroy', [$agency, $agent]) }}"
                                          data-confirm="Remove {{ $agent->user?->name ?? 'this agent' }} from {{ $agency->name }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-400">No agents assigned to this agency yet.</p>
                    @endforelse
                </div>

                @can('agencies.edit')
                    <form method="POST" action="{{ route('admin.agencies.agents.store', $agency) }}"
                          class="mt-4 pt-4 border-t border-slate-100 dark:border-night-700 flex flex-wrap items-end gap-2">
                        @csrf
                        <div class="flex-1 min-w-48">
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1" for="agent_user_email">
                                Add agent by user email
                            </label>
                            <input type="email" name="user_email" id="agent_user_email" required placeholder="agent@example.com"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-night-700 rounded-xl bg-white dark:bg-night-900 text-slate-700 dark:text-slate-200">
                        </div>
                        <input type="text" name="title" placeholder="Title (optional)"
                               class="w-40 px-3 py-2 text-sm border border-slate-200 dark:border-night-700 rounded-xl bg-white dark:bg-night-900 text-slate-700 dark:text-slate-200">
                        <button type="submit"
                                class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                            Add agent
                        </button>
                    </form>
                    @error('user_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @endcan
            </x-admin-card>

            {{-- Listings --}}
            @if($agency->listings->isNotEmpty())
                <x-admin-card title="Listings ({{ number_format($agency->listings_count) }})">
                    <div class="space-y-2">
                        @foreach($agency->listings as $listing)
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <div class="flex items-center gap-2 min-w-0">
                                    <a href="{{ route('admin.listings.show', $listing) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:underline truncate">
                                        {{ $listing->title }}
                                    </a>
                                    <span class="text-xs text-slate-400">{{ $listing->zone?->name }}</span>
                                </div>
                                <x-admin-badge :color="match($listing->status) {
                                    'active' => 'green', 'pending' => 'yellow', 'rejected' => 'red', 'archived' => 'gray', default => 'gray'
                                }">{{ ucfirst($listing->status) }}</x-admin-badge>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        <div class="space-y-5">
            <x-admin-card title="Owner">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center shrink-0">
                        @include('admin.partials.icon', ['name' => 'user-circle', 'class' => 'w-6 h-6 text-indigo-600 dark:text-indigo-400'])
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ $agency->owner?->name ?? 'Unknown' }}</p>
                        <p class="break-all text-xs text-slate-500 sm:break-normal">{{ $agency->owner?->email }}</p>
                        <p class="text-xs text-slate-400">{{ $agency->owner?->phone }}</p>
                    </div>
                </div>
            </x-admin-card>

            <x-admin-card title="Meta">
                <dl class="text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex justify-between gap-2">
                        <dt>ID</dt>
                        <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $agency->id }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Rating</dt>
                        <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $agency->rating ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Created</dt>
                        <dd>{{ $agency->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Updated</dt>
                        <dd>{{ $agency->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                    @if($agency->trashed())
                        <div class="flex justify-between gap-2 text-red-500">
                            <dt>Deleted</dt>
                            <dd>{{ $agency->deleted_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin-card>
        </div>
    </div>
</x-admin-layout>
