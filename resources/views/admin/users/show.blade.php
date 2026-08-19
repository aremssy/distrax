<x-admin-layout :title="$user->name">
    <x-admin-page-header :title="$user->name"
        :breadcrumbs="[['label' => 'Users', 'route' => 'admin.users.index'], ['label' => '#'.$user->id]]">
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                @can('users.edit')
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                        Edit
                    </a>
                @endcan
            </div>
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            {{-- User details --}}
            <x-admin-card title="User Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Name</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Email</dt>
                        <dd class="break-all text-slate-700 sm:break-normal dark:text-slate-200">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Phone</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $user->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Role</dt>
                        <dd><x-admin-badge color="indigo">{{ $user->role?->display_name ?? '—' }}</x-admin-badge></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Verification</dt>
                        <dd>
                            @php
                                $vColor = match($user->verification_status) {
                                    'verified' => 'green',
                                    'rejected' => 'red',
                                    'pending' => 'yellow',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin-badge :color="$vColor">{{ ucfirst($user->verification_status ?? 'unverified') }}</x-admin-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Email Verified</dt>
                        <dd>{{ $user->email_verified_at?->format('d M Y') ?? 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Currency</dt>
                        <dd>{{ strtoupper($user->currency ?? setting('default_currency', 'USD')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Language</dt>
                        <dd>{{ $user->language ?? setting('default_language', 'en') }}</dd>
                    </div>
                </dl>
            </x-admin-card>

            {{-- Listings --}}
            @if($user->listings->isNotEmpty())
                <x-admin-card title="Recent Listings ({{ $user->listings->count() }})">
                    <div class="space-y-2">
                        @foreach($user->listings as $listing)
                            <div class="flex min-w-0 items-center justify-between gap-2 text-sm">
                                <a href="{{ route('admin.listings.show', $listing) }}"
                                   class="min-w-0 truncate text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ $listing->title }}
                                </a>
                                <x-admin-badge :color="match($listing->status) {
                                    'active' => 'green', 'pending' => 'yellow', 'rejected' => 'red', 'archived' => 'gray', default => 'gray'
                                }">{{ ucfirst($listing->status) }}</x-admin-badge>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif

            {{-- Subscriptions --}}
            @if($user->subscriptions->isNotEmpty())
                <x-admin-card title="Recent Subscriptions ({{ $user->subscriptions->count() }})">
                    <div class="space-y-2">
                        @foreach($user->subscriptions as $sub)
                            <div class="flex flex-col items-start gap-1 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                                <div class="min-w-0">
                                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ $sub->plan?->name ?? '—' }}</span>
                                    <span class="text-slate-400 ml-2 text-xs">{{ $sub->starts_at?->format('d M Y') }} → {{ $sub->ends_at?->format('d M Y') }}</span>
                                </div>
                                <x-admin-badge :color="match($sub->status) {
                                    'active' => 'green', 'expired' => 'gray', 'cancelled' => 'red', default => 'yellow'
                                }">{{ ucfirst($sub->status) }}</x-admin-badge>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif

            {{-- Reviews --}}
            @if($user->reviews->isNotEmpty())
                <x-admin-card title="Recent Reviews ({{ $user->reviews->count() }})">
                    <div class="space-y-3">
                        @foreach($user->reviews as $review)
                            <div class="text-sm border-b border-slate-100 dark:border-night-700 pb-2 last:border-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                                    <span class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                @if($review->body)
                                    <p class="text-slate-600 dark:text-slate-400 mt-0.5 line-clamp-2">{{ $review->body }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Status card --}}
            <x-admin-card title="Account Status">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Blocked</span>
                        @if($user->is_blocked)
                            <x-admin-badge color="red">Yes</x-admin-badge>
                        @else
                            <x-admin-badge color="green">No</x-admin-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Last active</span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $user->last_active_at?->diffForHumans() ?? 'Never' }}</span>
                    </div>
                </div>
            </x-admin-card>

            {{-- Wallet --}}
            <x-admin-card title="Wallet">
                @if($user->wallet)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-800 dark:text-slate-100 font-mono">
                            {{ number_format($user->wallet->balance) }}
                        </p>
                        <p class="text-xs text-slate-500">{{ strtoupper($user->wallet->currency ?? 'USD') }}</p>
                    </div>
                @else
                    <p class="text-sm text-slate-400">No wallet created.</p>
                @endif
            </x-admin-card>

            {{-- Meta --}}
            <x-admin-card title="Meta">
                <dl class="text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex justify-between gap-2">
                        <dt>ID</dt>
                        <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $user->id }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Created</dt>
                        <dd>{{ $user->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Updated</dt>
                        <dd>{{ $user->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                    @if($user->trashed())
                        <div class="flex justify-between gap-2 text-red-500">
                            <dt>Deleted</dt>
                            <dd>{{ $user->deleted_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin-card>
        </div>
    </div>
</x-admin-layout>
