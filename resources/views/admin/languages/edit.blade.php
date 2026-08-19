<x-admin-layout title="Edit Language">
    <x-admin-page-header title="Edit Language"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Languages', 'route' => 'admin.languages.index'], ['label' => $language->name]]" />

    <form method="POST" action="{{ route('admin.languages.update', $language) }}">
        @csrf @method('PUT')
        <x-admin-card title="Language Details">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <x-admin.form.input name="code"        label="Locale Code"   :value="old('code', $language->code)"               required />
                <x-admin.form.input name="name"        label="Name"          :value="old('name', $language->name)"               required />
                <x-admin.form.input name="native_name" label="Native Name"   :value="old('native_name', $language->native_name)" required />
                <x-admin.form.select name="direction"  label="Text Direction">
                    <option value="ltr" @selected(old('direction', $language->direction) === 'ltr')>LTR (Left to Right)</option>
                    <option value="rtl" @selected(old('direction', $language->direction) === 'rtl')>RTL (Right to Left)</option>
                </x-admin.form.select>
                <x-admin.form.input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', $language->sort_order)" min="0" />
                <div class="flex flex-col gap-3 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $language->is_active))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $language->is_default))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Set as default</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Save Changes</button>
                <a href="{{ route('admin.languages.index') }}" class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
            </div>
        </x-admin-card>
    </form>
</x-admin-layout>
