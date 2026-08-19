<x-admin-layout title="New Agency">
    <x-admin-page-header title="New Agency"
        :breadcrumbs="[['label' => 'Agencies', 'route' => 'admin.agencies.index'], ['label' => 'New']]"
        description="Register a new agency under an existing user account." />

    <form method="POST" action="{{ route('admin.agencies.store') }}" enctype="multipart/form-data">
        @csrf

        <x-admin-card title="Agency Details">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <x-admin.form.input name="owner_email" label="Owner Email" type="email" :value="old('owner_email')" required />
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.select name="zone_id" label="Zone">
                    <option value="">—</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected(old('zone_id') == $zone->id)>{{ $zone->name }}</option>
                    @endforeach
                </x-admin.form.select>
                <x-admin.form.input name="phone" label="Phone" :value="old('phone')" />
                <x-admin.form.input name="email" label="Email" type="email" :value="old('email')" />
                <x-admin.form.input name="website" label="Website" type="url" :value="old('website')" placeholder="https://example.com" />
                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.input name="address" label="Address" :value="old('address')" />
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.textarea name="description" label="Description" rows="4">{{ old('description') }}</x-admin.form.textarea>
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.input name="logo" label="Logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB." />
                </div>
            </div>
        </x-admin-card>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Create Agency
            </button>
            <a href="{{ route('admin.agencies.index') }}"
               class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Cancel
            </a>
        </div>
    </form>
</x-admin-layout>
