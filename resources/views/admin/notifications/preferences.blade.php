<x-admin-layout title="Notification Preferences">
    <x-admin-page-header title="Notification Preferences"
        :breadcrumbs="[['label' => 'Notifications', 'route' => 'admin.notifications.index'], ['label' => 'Preferences']]"
        description="Configure which notifications you receive and how." />

    <x-admin-card>
        <form method="POST" action="{{ route('admin.notifications.preferences.update') }}" class="space-y-6">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-night-700">
                            <th class="py-2 pr-4 text-left text-xs font-semibold text-slate-500 uppercase">Event</th>
                            @foreach($channels as $channel)
                                <th class="py-2 px-3 text-center text-xs font-semibold text-slate-500 uppercase">{{ ucfirst($channel) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                        @foreach($events as $event)
                            <tr>
                                <td class="py-2.5 pr-4 text-sm text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $event)) }}</td>
                                @foreach($channels as $channel)
                                    @php $key = $event.'.'.$channel; @endphp
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="hidden" name="preferences[{{ $loop->parent->index }}][event]" value="{{ $event }}">
                                        <input type="hidden" name="preferences[{{ $loop->parent->index }}][channel]" value="{{ $channel }}">
                                        <input type="checkbox"
                                               name="preferences[{{ $loop->parent->index }}][enabled]"
                                               value="1"
                                               {{ ($preferences->get($key)?->enabled ?? true) ? 'checked' : '' }}
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save Preferences</button>
            </div>
        </form>
    </x-admin-card>
</x-admin-layout>
