<x-admin-layout title="Campaigns">
    <x-admin-page-header title="Campaigns"
        :breadcrumbs="[['label' => 'Campaigns']]"
        description="Manage marketing campaigns.">
        <x-slot name="actions">
            @can('marketing.edit')
                <button onclick="openModal('create-campaign-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.campaigns.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All statuses</option>
                @foreach (['draft', 'scheduled', 'sent', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="channel" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All channels</option>
                @foreach (['email', 'push', 'sms'] as $c)
                    <option value="{{ $c }}" @selected(request('channel') === $c)>{{ ucfirst($c) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status', 'channel']))
                <a href="{{ route('admin.campaigns.index') }}"
                   class="ml-auto flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Name</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Channel</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Sent To</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Scheduled</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Created By</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($campaigns as $campaign)
                        @php
                            $segments = collect($campaign->target_segments ?? [])
                                ->map(fn ($segment) => ucfirst(str_replace('_', ' ', (string) $segment)))
                                ->all();
                            $secondary = implode(' · ', array_filter([ucfirst($campaign->channel), $segments ? implode(', ', $segments) : null]));
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $campaign->id }}</td>

                            <td class="min-w-52 max-w-72 px-3 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.campaigns.show', $campaign) }}"
                                       class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                        {{ $campaign->name }}
                                    </a>
                                    <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $secondary }}</p>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ ucfirst($campaign->channel) }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $campaign->sent_count ?? '—' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="match($campaign->status) { 'draft' => 'yellow', 'scheduled' => 'blue', 'sent' => 'green', 'cancelled' => 'gray', default => 'gray' }">
                                    {{ ucfirst($campaign->status) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $campaign->scheduled_at?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $campaign->creator?->name ?? '—' }}</td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.campaigns.show', $campaign) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('marketing.edit')
                                            @if (in_array($campaign->status, ['draft', 'scheduled']))
                                                <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}"
                                                      data-confirm="Send this campaign?">
                                                    @csrf
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                        @include('admin.partials.icon', ['name' => 'share', 'class' => 'w-4 h-4'])
                                                        Send
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($campaign->status !== 'sent')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}"
                                                      data-confirm="Delete this campaign?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-slate-400">No campaigns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $campaigns->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('marketing.edit')
        <x-admin-modal id="create-campaign-modal" title="Create Campaign">
            <form method="POST" action="{{ route('admin.campaigns.store') }}" class="space-y-4">
                @csrf
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.select name="channel" label="Channel">
                    <option value="email">Email</option>
                    <option value="push">Push</option>
                    <option value="sms">SMS</option>
                </x-admin.form.select>
                <x-admin.form.select name="email_template_id" label="Email Template">
                    <option value="">None</option>
                </x-admin.form.select>
                <x-admin.form.input name="subject" label="Subject" :value="old('subject')" />
                <x-admin.form.textarea name="content" label="Content" rows="4">{{ old('content') }}</x-admin.form.textarea>
                @include('admin.campaigns.partials.segments')
                <x-admin.form.select name="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                </x-admin.form.select>
                <x-admin.form.input name="scheduled_at" label="Scheduled At" type="datetime-local" :value="old('scheduled_at')" />
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-campaign-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const templateSelect = document.querySelector('#create-campaign-modal select[name="email_template_id"]');
                if (templateSelect) {
                    fetch('{{ route("admin.campaigns.templates") }}')
                        .then(r => r.json())
                        .then(data => {
                            data.templates.forEach(tpl => {
                                const opt = document.createElement('option');
                                opt.value = tpl.id;
                                opt.textContent = tpl.name + ' (' + tpl.key + ')';
                                templateSelect.appendChild(opt);
                            });
                        });
                }
            });
        </script>
        @endpush
    @endcan
</x-admin-layout>
