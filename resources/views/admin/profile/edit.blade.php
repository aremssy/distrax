<x-admin-layout title="My Profile">
    <x-admin-page-header title="My Profile"
        :breadcrumbs="[['label' => 'My Profile']]"
        description="Manage your personal details and account password." />

    @php
        $avatarUrl = $user->avatar
            ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/'.$user->avatar))
            : null;
    @endphp

    {{-- Identity summary --}}
    <x-admin-card class="mb-6">
        <div class="flex items-center gap-4">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-slate-100 dark:ring-night-700">
            @else
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand text-lg font-bold text-white">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
            @endif
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                @if ($user->role)
                    <div class="mt-1.5">
                        <x-admin-badge color="indigo">{{ $user->role->display_name }}</x-admin-badge>
                    </div>
                @endif
            </div>
        </div>
    </x-admin-card>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- Profile details --}}
        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <x-admin-card title="Profile Details">
                <div class="mb-5">
                    <x-admin.form.input name="avatar" label="Avatar" type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        hint="Maximum 2 MB. Uploading a new avatar replaces the current one." />
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-admin.form.input name="name" label="Name" :value="old('name', $user->name)" required />
                    <x-admin.form.input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
                    <div class="sm:col-span-2">
                        <x-admin.form.input name="phone" label="Phone" :value="old('phone', $user->phone)" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                        Save Changes
                    </button>
                </div>
            </x-admin-card>
        </form>

        {{-- Change password --}}
        <form method="POST" action="{{ route('admin.profile.password') }}">
            @csrf @method('PUT')

            <x-admin-card title="Change Password">
                <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
                    Choose a strong password you don't use anywhere else.
                </p>
                <div class="space-y-5">
                    <x-admin.form.password name="current_password" label="Current Password"
                        autocomplete="current-password" required />
                    <x-admin.form.password name="password" label="New Password"
                        autocomplete="new-password" required
                        hint="At least one uppercase and lowercase letter and a number." />
                    <x-admin.form.password name="password_confirmation" label="Confirm New Password"
                        autocomplete="new-password" required />
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                        Update Password
                    </button>
                </div>
            </x-admin-card>
        </form>

    </div>
</x-admin-layout>
