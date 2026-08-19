<x-admin-layout title="Create Role">
    <x-admin-page-header title="Create Role"
        :breadcrumbs="[['label' => 'Roles', 'route' => 'admin.roles.index'], ['label' => 'Create']]"
        description="Define a new role and select its permissions." />

    <div>
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf

            <x-admin-card title="Role Details">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="name" label="System Name" placeholder="e.g. content_manager"
                        :value="old('name')" required hint="Lowercase, no spaces. Used internally." />
                    <x-admin.form.input name="display_name" label="Display Name" placeholder="e.g. Content Manager"
                        :value="old('display_name')" required />
                </div>
            </x-admin-card>

            <x-admin-card title="Permissions" class="mt-4">
                @if($permissions->isEmpty())
                    <p class="text-sm text-slate-400">No permissions defined yet.</p>
                @else
                    <div class="space-y-6">
                        @foreach($permissions as $group => $groupPermissions)
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 capitalize">{{ $group }}</h4>
                                    <button type="button"
                                            onclick="toggleGroup(this, '{{ $group }}')"
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Select all
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
                                    @foreach($groupPermissions as $permission)
                                        <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                   class="group-{{ $group }} h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                   @checked(in_array($permission->id, old('permissions', [])))>
                                            {{ $permission->display_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin-card>

            <div class="mt-5 flex gap-3">
                <button type="submit"
                        class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Create Role
                </button>
                <a href="{{ route('admin.roles.index') }}"
                   class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function toggleGroup(btn, group) {
            const checkboxes = document.querySelectorAll('.group-' + group);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            btn.textContent = allChecked ? 'Select all' : 'Deselect all';
        }
    </script>
    @endpush
</x-admin-layout>
