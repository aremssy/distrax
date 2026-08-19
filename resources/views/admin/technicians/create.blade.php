<x-admin-layout title="Add Technician">
    <x-admin-page-header title="Add Technician"
        :breadcrumbs="[['label' => 'Technicians', 'route' => 'admin.technicians.index'], ['label' => 'Add']]"
        description="Create a technician profile for an existing user account." />

    <form method="POST" action="{{ route('admin.technicians.store') }}">
        @csrf

        <x-admin-card title="Technician Details">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.input name="user_email" label="User Email" type="email"
                        :value="old('user_email')" placeholder="name@example.com" required
                        hint="The user must already have an account. They can only hold one technician profile." />
                </div>

                <x-admin.form.select name="technician_category_id" label="Service Category" required>
                    <option value="">Select a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('technician_category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.select name="zone_id" label="Zone">
                    <option value="">— None —</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" @selected((int) old('zone_id') === $zone->id)>{{ $zone->name }} ({{ $zone->type }})</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.select name="status" label="Status" required>
                    @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.input name="experience_years" label="Experience (years)" type="number"
                    :value="old('experience_years', 0)" min="0" max="80" required />

                <x-admin.form.input name="hourly_rate" label="Hourly Rate" type="number"
                    :value="old('hourly_rate')" min="0" hint="Leave empty if not fixed." />

                <x-admin.form.input name="skills" label="Skills"
                    :value="is_array(old('skills')) ? implode(', ', old('skills')) : old('skills')"
                    placeholder="Wiring, AC servicing" hint="Comma separated." />

                <div class="md:col-span-2 xl:col-span-3">
                    <x-admin.form.textarea name="bio" label="Bio" rows="4" maxlength="1000">{{ old('bio') }}</x-admin.form.textarea>
                </div>

                <div class="flex flex-col gap-3 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_available" value="1" @checked(old('is_available', true))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Available for work</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified'))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Verified</span>
                    </label>
                </div>
            </div>
        </x-admin-card>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Add Technician
            </button>
            <a href="{{ route('admin.technicians.index') }}"
               class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Cancel
            </a>
        </div>
    </form>
</x-admin-layout>
