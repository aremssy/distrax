<x-admin-layout title="Referral Rule Detail">
    <x-admin-page-header :title="$rule->name"
        :breadcrumbs="[['label' => 'Referral Rules', 'route' => 'admin.referral-rules.index'], ['label' => $rule->name]]"
        description="Edit referral rule details.">
        <x-slot name="actions">
            @can('marketing.edit')
                <form method="POST" action="{{ route('admin.referral-rules.destroy', $rule) }}"
                      data-confirm="Delete this rule?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Delete
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-admin-page-header>

    <x-admin-card title="Rule Details">
        <form method="POST" action="{{ route('admin.referral-rules.update', $rule) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" :value="old('name', $rule->name)" required />
                <x-admin.form.select name="reward_type" label="Reward Type">
                    <option value="credit" @selected($rule->reward_type === 'credit')>Credit</option>
                    <option value="discount_percent" @selected($rule->reward_type === 'discount_percent')>Discount Percent</option>
                    <option value="discount_fixed" @selected($rule->reward_type === 'discount_fixed')>Discount Fixed</option>
                    <option value="free_listing" @selected($rule->reward_type === 'free_listing')>Free Listing</option>
                    <option value="featured_listing" @selected($rule->reward_type === 'featured_listing')>Featured Listing</option>
                </x-admin.form.select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="reward_value" label="Reward Value" type="number" :value="old('reward_value', $rule->reward_value)" required />
                <x-admin.form.input name="max_referrals" label="Max Referrals" type="number" :value="old('max_referrals', $rule->max_referrals)" hint="Leave empty for unlimited" />
            </div>
            <x-admin.form.textarea name="reward_description" label="Description" rows="2">{{ old('reward_description', $rule->reward_description) }}</x-admin.form.textarea>
            <x-admin.form.input name="min_purchase_amount" label="Min Purchase Amount" type="number" :value="old('min_purchase_amount', $rule->min_purchase_amount)" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.form.input name="starts_at" label="Starts At" type="date" :value="old('starts_at', $rule->starts_at?->format('Y-m-d'))" />
                <x-admin.form.input name="expires_at" label="Expires At" type="date" :value="old('expires_at', $rule->expires_at?->format('Y-m-d'))" />
            </div>
            <x-admin.form.select name="is_active" label="Status">
                <option value="1" @selected(old('is_active', $rule->is_active))>Active</option>
                <option value="0" @selected(!old('is_active', $rule->is_active))>Inactive</option>
            </x-admin.form.select>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.referral-rules.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Update</button>
            </div>
        </form>
    </x-admin-card>
</x-admin-layout>
