<x-admin-layout title="Account Deletion Requests">
    <x-admin-page-header title="Account Deletion Requests"
        :breadcrumbs="[['label' => 'Users', 'url' => route('admin.users.index')], ['label' => 'Deletion Requests']]"
        description="Users who have requested their account be deleted. Approve to permanently delete, or reject to clear the request and keep the account active." />

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">User</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Role</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Requested At</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($requests as $user)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-56 max-w-72 px-5 py-3.5">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                    {{ $user->name }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $user->email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge color="indigo">{{ $user->role?->display_name ?? '—' }}</x-admin-badge>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $user->deletion_requested_at->format('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.users.deletion-requests.reject', $user) }}">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:text-slate-300 dark:hover:bg-night-800">
                                            Reject
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.deletion-requests.approve', $user) }}"
                                          onsubmit="return confirm('Permanently delete this account? This cannot be undone.');">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                                            Approve & Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-sm text-slate-400">No pending deletion requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $requests->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
