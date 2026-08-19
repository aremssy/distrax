<x-admin-layout title="Edit Template">
    <x-admin-page-header title="Edit: {{ $emailTemplate->name }}"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Templates', 'route' => 'admin.email-templates.index'], ['label' => $emailTemplate->name]]" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}">
                @csrf @method('PUT')
                <x-admin-card title="Template Content">
                    <div class="space-y-5">
                        @if ($emailTemplate->channel === 'email')
                            <x-admin.form.input name="subject" label="Subject" :value="old('subject', $emailTemplate->subject)" />
                        @endif
                        <x-admin.form.textarea name="body" label="Body" :rows="16">{{ old('body', $emailTemplate->body) }}</x-admin.form.textarea>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($emailTemplate->is_active)
                                   class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Active</span>
                        </label>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save Template</button>
                        <a href="{{ route('admin.email-templates.index') }}" class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                    </div>
                </x-admin-card>
            </form>
        </div>

        <div class="space-y-4">
            <x-admin-card title="Template Info">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Key</dt>
                        <dd class="mt-0.5 break-all font-mono text-slate-800 sm:break-normal dark:text-slate-200">{{ $emailTemplate->key }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Channel</dt>
                        <dd class="mt-0.5"><x-admin-badge :color="$emailTemplate->channel === 'email' ? 'blue' : 'purple'">{{ strtoupper($emailTemplate->channel) }}</x-admin-badge></dd>
                    </div>
                </dl>
            </x-admin-card>

            @if ($emailTemplate->variables)
                <x-admin-card title="Available Variables">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Click to copy placeholder.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($emailTemplate->variables as $var)
                            @php($placeholder = '{{ '.$var.' }}')
                            <button type="button"
                                onclick="navigator.clipboard.writeText(@js($placeholder))"
                                class="max-w-full break-all rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700 sm:break-normal dark:bg-night-800 dark:text-slate-300 dark:hover:bg-indigo-500/15 dark:hover:text-indigo-400">
                                {{ $placeholder }}
                            </button>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>
    </div>
</x-admin-layout>
