@extends('website.layouts.dashboard')
@section('title', __('Agreements').' | '.config('app.name'))
@section('page-title', __('Agreements'))
@section('dashboard-content')
<div class="mx-auto max-w-4xl space-y-6">
    @unless ($hasAccess)
        <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6"><h2 class="font-bold text-indigo-950">{{ __('Agreement templates are locked') }}</h2><p class="mt-2 text-sm text-indigo-800">{{ __('Generating tenancy agreements from templates requires an eligible subscription. Choose a plan to unlock this feature.') }}</p><a href="{{ route('dashboard.wallet') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">{{ __('View plans') }}</a></section>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 font-bold">{{ __('Templates') }}</h2><div class="divide-y">@forelse ($templates as $template)<div class="flex items-center justify-between gap-3 px-5 py-3 text-sm"><span class="min-w-0">{{ $template->name }} @if ($template->is_default)<span class="ml-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ __('Default') }}</span>@endif</span><form class="shrink-0" action="{{ route('owner.rent-management.agreements.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('{{ __('Delete this template?') }}')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600" type="submit">{{ __('Delete') }}</button></form></div>@empty<p class="p-5 text-sm text-slate-500">{{ __('No templates yet.') }}</p>@endforelse</div><form class="space-y-3 border-t p-5" action="{{ route('owner.rent-management.agreements.templates.store') }}" method="POST">
            @csrf
            <label class="block text-xs font-semibold text-slate-600" for="template-name">{{ __('Template name') }}
                <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="template-name" name="name" required>
            </label>
            <label class="block text-xs font-semibold text-slate-600" for="template-body">{{ __('Template body') }}
                <textarea class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="template-body" name="body" rows="4" placeholder="Tenant @{{tenant_name}} pays @{{rent_amount}} for @{{property_title}} starting @{{start_date}}." required></textarea>
            </label>
            <p class="text-xs text-slate-500">{{ __('Available placeholders:') }} @{{owner_name}}, @{{tenant_name}}, @{{property_title}}, @{{rent_amount}}, @{{deposit_amount}}, @{{start_date}}, @{{end_date}}</p>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> {{ __('Set as default template') }}</label>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Save template') }}</button>
        </form></section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 font-bold">{{ __('Generated agreements') }}</h2><div class="divide-y">@forelse ($agreements as $agreement)<div class="flex items-center justify-between gap-3 px-5 py-3 text-sm"><span class="min-w-0">{{ $agreement->tenancy?->listing?->title }} &middot; {{ $agreement->generated_at->format('M j, Y') }}</span><a href="{{ route('owner.rent-management.agreements.show', $agreement) }}" class="shrink-0 font-semibold text-indigo-600">{{ __('View') }}</a></div>@empty<p class="p-5 text-sm text-slate-500">{{ __('No agreements generated yet.') }}</p>@endforelse</div>@if ($tenancies->isNotEmpty())
        <form class="flex flex-wrap items-end gap-2 border-t p-5" action="{{ route('owner.rent-management.agreements.generate') }}" method="POST">
            @csrf
            <label class="w-full text-xs font-semibold text-slate-600 sm:w-auto" for="generate-tenancy-id">{{ __('Tenancy') }}
                <select class="mt-1 block w-full min-w-0 rounded-lg border border-slate-200 px-3 py-2 text-sm sm:w-auto" id="generate-tenancy-id" name="tenancy_id" required><option value="">{{ __('Select tenancy') }}</option>@foreach ($tenancies as $tenancy)<option value="{{ $tenancy->id }}">{{ $tenancy->tenantName() }} &middot; {{ $tenancy->listing?->title }}</option>@endforeach</select>
            </label>
            <label class="w-full text-xs font-semibold text-slate-600 sm:w-auto" for="generate-template-id">{{ __('Template') }}
                <select class="mt-1 block w-full min-w-0 rounded-lg border border-slate-200 px-3 py-2 text-sm sm:w-auto" id="generate-template-id" name="agreement_template_id"><option value="">{{ __('Default template') }}</option>@foreach ($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select>
            </label>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Generate') }}</button>
        </form>
    @endif</section>
    @endunless
</div>
@endsection
