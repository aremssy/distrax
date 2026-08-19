<x-admin-layout title="Campaign Detail">
    <x-admin-page-header :title="$campaign->name"
        :breadcrumbs="[['label' => 'Campaigns', 'route' => 'admin.campaigns.index'], ['label' => $campaign->name]]"
        description="View and manage campaign details.">
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                @can('marketing.edit')
                    @if(in_array($campaign->status, ['draft', 'scheduled']))
                        <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}"
                              data-confirm="Send this campaign to selected segments?">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                Send Now
                            </button>
                        </form>
                    @endif
                    @if($campaign->status !== 'sent')
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}"
                              data-confirm="Delete this campaign?">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                Delete
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            @if($campaign->status === 'draft')
                <x-admin-card title="Campaign Content">
                    <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <x-admin.form.input name="name" label="Name" :value="old('name', $campaign->name)" required />
                        <x-admin.form.select name="channel" label="Channel">
                            <option value="email" @selected($campaign->channel === 'email')>Email</option>
                            <option value="push" @selected($campaign->channel === 'push')>Push</option>
                            <option value="sms" @selected($campaign->channel === 'sms')>SMS</option>
                        </x-admin.form.select>
                        @if($campaign->channel === 'email')
                            <x-admin.form.select name="email_template_id" label="Email Template">
                                <option value="">None</option>
                            </x-admin.form.select>
                        @endif
                        <x-admin.form.input name="subject" label="Subject" :value="old('subject', $campaign->subject)" />
                        <x-admin.form.textarea name="content" label="Content" rows="6">{{ old('content', $campaign->content) }}</x-admin.form.textarea>
                        @include('admin.campaigns.partials.segments', ['selected' => $campaign->target_segments ?? []])
                        <x-admin.form.select name="status" label="Status">
                            <option value="draft" @selected($campaign->status === 'draft')>Draft</option>
                            <option value="scheduled" @selected($campaign->status === 'scheduled')>Scheduled</option>
                            <option value="cancelled" @selected($campaign->status === 'cancelled')>Cancelled</option>
                        </x-admin.form.select>
                        <x-admin.form.input name="scheduled_at" label="Scheduled At" type="datetime-local" :value="old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i'))" />
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
                        </div>
                    </form>
                </x-admin-card>
            @else
                <x-admin-card title="Campaign Details">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Name</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $campaign->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Channel</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-100">{{ ucfirst($campaign->channel) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                            <dd>
                                <x-admin-badge :color="match($campaign->status) { 'draft' => 'yellow', 'scheduled' => 'blue', 'sent' => 'green', 'cancelled' => 'gray', default => 'gray' }">
                                    {{ ucfirst($campaign->status) }}
                                </x-admin-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Sent Count</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $campaign->sent_count ?? 0 }}</dd>
                        </div>
                    </dl>
                    @if($campaign->subject)
                        <div class="mt-4">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">Subject</dt>
                            <dd class="text-sm font-medium text-slate-800 dark:text-slate-100 mt-1">{{ $campaign->subject }}</dd>
                        </div>
                    @endif
                    @if($campaign->content)
                        <div class="mt-4">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">Content</dt>
                            <dd class="mt-1 whitespace-pre-line break-words text-sm text-slate-700 dark:text-slate-300">{{ $campaign->content }}</dd>
                        </div>
                    @endif
                </x-admin-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin-card title="Target Segments">
                <div class="space-y-2">
                    @if($campaign->target_segments)
                        @foreach($campaign->target_segments as $segment)
                            <x-admin-badge color="indigo">{{ ucfirst(str_replace('_', ' ', $segment)) }}</x-admin-badge>
                        @endforeach
                    @else
                        <p class="text-sm text-slate-400">No segments selected.</p>
                    @endif
                </div>
            </x-admin-card>

            <x-admin-card title="Timeline">
                <div class="text-xs text-slate-500 dark:text-slate-400 space-y-2">
                    <p><span class="font-medium text-slate-600 dark:text-slate-300">Created:</span> {{ $campaign->created_at->format('M d, Y h:i A') }}</p>
                    <p><span class="font-medium text-slate-600 dark:text-slate-300">Updated:</span> {{ $campaign->updated_at->format('M d, Y h:i A') }}</p>
                    @if($campaign->scheduled_at)
                        <p><span class="font-medium text-slate-600 dark:text-slate-300">Scheduled:</span> {{ $campaign->scheduled_at->format('M d, Y h:i A') }}</p>
                    @endif
                    @if($campaign->sent_at)
                        <p><span class="font-medium text-slate-600 dark:text-slate-300">Sent:</span> {{ $campaign->sent_at->format('M d, Y h:i A') }}</p>
                    @endif
                    <p><span class="font-medium text-slate-600 dark:text-slate-300">Created by:</span> {{ $campaign->creator?->name ?? '—' }}</p>
                </div>
            </x-admin-card>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const templateSelect = document.querySelector('select[name="email_template_id"]');
            if (templateSelect) {
                fetch('{{ route("admin.campaigns.templates") }}')
                    .then(r => r.json())
                    .then(data => {
                        data.templates.forEach(tpl => {
                            const opt = document.createElement('option');
                            opt.value = tpl.id;
                            opt.textContent = tpl.name + ' (' + tpl.key + ')';
                            if ('{{ $campaign->email_template_id }}' == tpl.id) opt.selected = true;
                            templateSelect.appendChild(opt);
                        });
                    });
            }
        });
    </script>
    @endpush
</x-admin-layout>
