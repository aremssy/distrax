<x-admin-layout :title="'Edit: '.$agency->name">
    <x-admin-page-header :title="'Edit: '.$agency->name"
        :breadcrumbs="[['label' => 'Agencies', 'route' => 'admin.agencies.index'], ['label' => '#'.$agency->id], ['label' => 'Edit']]"
        description="Update agency details and settings." />

    <form method="POST" action="{{ route('admin.agencies.update', $agency) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <x-admin-card title="Agency Details">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <x-admin.form.input name="name" label="Name" :value="old('name', $agency->name)" required />
                <x-admin.form.input name="phone" label="Phone" :value="old('phone', $agency->phone)" />
                <x-admin.form.input name="email" label="Email" type="email" :value="old('email', $agency->email)" />
                <x-admin.form.input name="website" label="Website" type="url" :value="old('website', $agency->website)" placeholder="https://example.com" />
                <div class="md:col-span-2">
                    <x-admin.form.input name="address" label="Address" :value="old('address', $agency->address)" />
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.textarea name="description" label="Description" rows="4">{{ old('description', $agency->description) }}</x-admin.form.textarea>
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    @if ($agency->logo)
                        @php
                            $logoUrl = str_starts_with($agency->logo, 'http') ? $agency->logo : asset('storage/'.$agency->logo);
                        @endphp
                        <img src="{{ $logoUrl }}" alt="{{ $agency->name }}" class="mb-2 h-20 w-20 rounded-lg object-cover">
                    @endif
                    <x-admin.form.input name="logo" label="Logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Uploading a new logo replaces the current one." />
                </div>
                <x-admin.form.select name="status" label="Status" required>
                    <option value="active" @selected(old('status', $agency->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $agency->status) === 'inactive')>Inactive</option>
                    <option value="suspended" @selected(old('status', $agency->status) === 'suspended')>Suspended</option>
                </x-admin.form.select>
                <label class="flex items-center gap-2 pt-6 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_verified" value="1"
                        @checked(old('is_verified', $agency->is_verified))
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Verified
                </label>
            </div>
        </x-admin-card>

        <div class="mt-5 flex flex-wrap gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Save Changes
            </button>
            <a href="{{ route('admin.agencies.show', $agency) }}"
               class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Cancel
            </a>
            @can('agencies.delete')
                <form method="POST" action="{{ route('admin.agencies.destroy', $agency) }}"
                      class="ml-auto"
                      data-confirm="Delete {{ addslashes($agency->name) }}?">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete Agency
                    </button>
                </form>
            @endcan
        </div>
    </form>
</x-admin-layout>
