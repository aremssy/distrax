@extends('website.layouts.dashboard')
@section('title', ($tenancy ? __('Edit Tenancy') : __('Add Tenancy')).' | '.config('app.name'))
@section('page-title', $tenancy ? __('Edit Tenancy') : __('Add Tenancy'))
@section('dashboard-content')
<div class="mx-auto max-w-4xl space-y-6">
    @if ($errors->any())<div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="space-y-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6" action="{{ $tenancy ? route('owner.rent-management.tenancies.update', $tenancy) : route('owner.rent-management.tenancies.store') }}" method="POST">@csrf @if ($tenancy) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            @unless ($tenancy)
                <label class="text-sm font-semibold">{{ __('Property') }}<select class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="property_listing_id" required><option value="">{{ __('Select listing') }}</option>@foreach ($listings as $listing)<option value="{{ $listing->id }}" @selected((int) old('property_listing_id') === $listing->id)>{{ $listing->title }}</option>@endforeach</select></label>
                @if (($features ?? []) && in_array('multi_unit', $features, true))<label class="text-sm font-semibold">{{ __('Unit (optional)') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="unit_id" type="number" min="1" value="{{ old('unit_id') }}" placeholder="{{ __('Unit ID — see Units page on the listing') }}"></label>@endif
                <label class="text-sm font-semibold">{{ __('Tenant name') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="tenant_name" value="{{ old('tenant_name') }}" placeholder="{{ __('For tenants without a platform account') }}"></label>
                <x-website.phone-input name="tenant_phone" label="{{ __('Tenant phone') }}" :value="old('tenant_phone')" />
                <label class="text-sm font-semibold">{{ __('Tenant email') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="tenant_email" type="email" value="{{ old('tenant_email') }}"></label>
                <label class="text-sm font-semibold">{{ __('Start date') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="start_date" type="date" value="{{ old('start_date') }}" required></label>
            @endunless
            <label class="text-sm font-semibold">{{ __('Monthly rent') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="rent_amount" type="number" min="0" value="{{ old('rent_amount', $tenancy?->rent_amount) }}" required></label>
            <label class="text-sm font-semibold">{{ __('Deposit') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="deposit_amount" type="number" min="0" value="{{ old('deposit_amount', $tenancy?->deposit_amount ?? 0) }}"></label>
            <label class="text-sm font-semibold">{{ __('Rent due day of month') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="due_day_of_month" type="number" min="1" max="28" value="{{ old('due_day_of_month', $tenancy?->due_day_of_month ?? 1) }}"></label>
            @if ($tenancy)
                <label class="text-sm font-semibold">{{ __('End date') }}<input class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="end_date" type="date" value="{{ old('end_date', $tenancy->end_date?->toDateString()) }}"></label>
                <label class="text-sm font-semibold">{{ __('Status') }}<select class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5" name="status">@foreach (['active','ended','terminated'] as $status)<option value="{{ $status }}" @selected(old('status', $tenancy->status) === $status)>{{ __(ucfirst($status)) }}</option>@endforeach</select></label>
            @endif
        </div>
        <button class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white sm:w-auto" type="submit">{{ $tenancy ? __('Save changes') : __('Add tenancy') }}</button>
    </form>
</div>
@endsection
