<x-admin-layout title="Add Currency">
    <x-admin-page-header title="Add Currency"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Currencies', 'route' => 'admin.currencies.index'], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.currencies.store') }}">
        @csrf
        <x-admin-card title="Currency Details">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <x-admin.form.input name="code"   label="Code"   :value="old('code')"   placeholder="USD" hint="3-letter ISO code" required />
                <x-admin.form.input name="name"   label="Name"   :value="old('name')"   placeholder="US Dollar" required />
                <x-admin.form.input name="symbol" label="Symbol" :value="old('symbol')" placeholder="$" required />
                <x-admin.form.select name="symbol_position" label="Symbol Position">
                    <option value="before" @selected(old('symbol_position', 'before') === 'before')>Before amount ($100)</option>
                    <option value="after"  @selected(old('symbol_position') === 'after')>After amount (100$)</option>
                </x-admin.form.select>
                <x-admin.form.input name="exchange_rate"      label="Exchange Rate"       type="number" step="0.000001" :value="old('exchange_rate', 1)" required />
                <x-admin.form.input name="decimal_places"     label="Decimal Places"      type="number" :value="old('decimal_places', 2)" min="0" max="8" />
                <x-admin.form.input name="thousands_separator" label="Thousands Separator" :value="old('thousands_separator', ',')" />
                <x-admin.form.input name="decimal_separator"  label="Decimal Separator"   :value="old('decimal_separator', '.')" />
                <x-admin.form.input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', 0)" min="0" />
                <div class="flex flex-col gap-3 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Set as default</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Add Currency</button>
                <a href="{{ route('admin.currencies.index') }}" class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
            </div>
        </x-admin-card>
    </form>
</x-admin-layout>
