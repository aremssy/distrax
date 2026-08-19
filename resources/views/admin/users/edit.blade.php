<x-admin-layout :title="'Edit: '.$user->name">
    <x-admin-page-header :title="'Edit: '.$user->name"
        :breadcrumbs="[['label' => 'Users', 'route' => 'admin.users.index'], ['label' => '#'.$user->id], ['label' => 'Edit']]"
        description="Update user details, role, and account status." />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <x-admin-card title="User Details">
            <div class="mb-5">
                @if ($user->avatar)
                    @php
                        $avatarUrl = str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/'.$user->avatar);
                    @endphp
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="mb-2 h-20 w-20 rounded-full object-cover">
                @endif
                <x-admin.form.input name="avatar" label="Avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new avatar replaces the current one." />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <x-admin.form.input name="name" label="Name" :value="old('name', $user->name)" required />
                <x-admin.form.input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
                <x-admin.form.input name="phone" label="Phone" :value="old('phone', $user->phone)" />

                <x-admin.form.select name="role_id" label="Role" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.select name="verification_status" label="Verification Status" required>
                    <option value="unverified" @selected(old('verification_status', $user->verification_status) === 'unverified')>Unverified</option>
                    <option value="pending" @selected(old('verification_status', $user->verification_status) === 'pending')>Pending</option>
                    <option value="verified" @selected(old('verification_status', $user->verification_status) === 'verified')>Verified</option>
                    <option value="rejected" @selected(old('verification_status', $user->verification_status) === 'rejected')>Rejected</option>
                </x-admin.form.select>

                <label class="flex items-center gap-2 pt-6 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_blocked" value="1"
                        @checked(old('is_blocked', $user->is_blocked))
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Blocked
                </label>
            </div>
        </x-admin-card>

        <div class="mt-5 flex flex-wrap gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Save Changes
            </button>
            <a href="{{ route('admin.users.show', $user) }}"
               class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Cancel
            </a>
            @can('users.delete')
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                      class="ml-auto"
                      data-confirm="Delete {{ addslashes($user->name) }}? This will soft-delete the user.">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete User
                    </button>
                </form>
            @endcan
        </div>
    </form>
</x-admin-layout>
