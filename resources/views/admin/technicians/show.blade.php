<x-admin-layout title="Technician Detail">
    <x-admin-page-header title="Technician Detail"
        :breadcrumbs="[['label' => 'Technicians', 'route' => 'admin.technicians.index'], ['label' => $technician->user?->name ?? '#' . $technician->id]]"
        description="View and manage technician information.">
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                @can('technicians.edit')
                    <a href="{{ route('admin.technicians.edit', $technician) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                        Edit
                    </a>
                @endcan
                @can('technicians.edit')
                    @if($technician->status === 'pending')
                        <form method="POST" action="{{ route('admin.technicians.approve', $technician) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                Approve
                            </button>
                        </form>
                    @endif
                    @if($technician->status !== 'suspended')
                        <form method="POST" action="{{ route('admin.technicians.suspend', $technician) }}"
                              data-confirm="Suspend this technician?">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                Suspend
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-admin-card title="Profile">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Name</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->user?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Email</dt>
                        <dd class="break-all font-medium text-slate-800 sm:break-normal dark:text-slate-100">{{ $technician->user?->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Phone</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->user?->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Category</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Zone</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->zone?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Experience</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->experience_years ?? 0 }} years</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Hourly Rate</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $technician->hourly_rate ? money($technician->hourly_rate) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Rating</dt>
                        <dd class="font-medium text-slate-800 dark:text-slate-100">{{ number_format($technician->reviews_avg_rating ?? 0, 1) }} / 5</dd>
                    </div>
                </dl>
            </x-admin-card>

            <x-admin-card title="Bio">
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ $technician->bio ?? 'No bio provided.' }}</p>
            </x-admin-card>

            @if($technician->skills)
                <x-admin-card title="Skills">
                    <div class="flex flex-wrap gap-2">
                        @foreach($technician->skills as $skill)
                            <x-admin-badge color="indigo">{{ $skill }}</x-admin-badge>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin-card title="Status">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Status</span>
                        <x-admin-badge :color="match($technician->status) { 'active' => 'green', 'pending' => 'yellow', 'inactive' => 'gray', 'suspended' => 'red', default => 'gray' }">
                            {{ ucfirst($technician->status) }}
                        </x-admin-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Available</span>
                        <x-admin-badge :color="$technician->is_available ? 'green' : 'gray'">
                            {{ $technician->is_available ? 'Yes' : 'No' }}
                        </x-admin-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Verified</span>
                        <x-admin-badge :color="$technician->is_verified ? 'blue' : 'gray'">
                            {{ $technician->is_verified ? 'Yes' : 'No' }}
                        </x-admin-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Member Since</span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $technician->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </x-admin-card>

            @if($technician->reviews_count > 0)
                <x-admin-card title="Recent Reviews ({{ number_format($technician->reviews_count) }})">
                    <div class="divide-y divide-slate-100 dark:divide-night-800">
                        @foreach($technician->reviews as $review)
                            <div class="py-2.5 first:pt-0 last:pb-0">
                                <div class="flex min-w-0 items-center justify-between gap-2 text-sm">
                                    <span class="min-w-0 truncate font-medium text-slate-700 dark:text-slate-300">{{ $review->reviewer?->name ?? '—' }}</span>
                                    <span class="shrink-0 text-yellow-500">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
                                </div>
                                @if($review->comment)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ Str::limit($review->comment, 100) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>
    </div>
</x-admin-layout>
