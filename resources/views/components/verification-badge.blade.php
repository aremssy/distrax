@php
    $config = match ($status) {
        'distrax_verified' => ['label' => 'Distrax Verified', 'dot' => 'bg-emerald-500', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'],
        'disclosure_required' => ['label' => 'Verified \u2014 Disclosure Required', 'dot' => 'bg-amber-500', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'],
        'in_progress' => ['label' => 'Verification in Progress', 'dot' => 'bg-sky-500', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300'],
        'under_legal_review' => ['label' => 'Under Legal Review', 'dot' => 'bg-orange-500', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300'],
        'not_verified' => ['label' => 'Not Verified', 'dot' => 'bg-rose-500', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'],
        default => ['label' => 'Not Verified', 'dot' => 'bg-slate-400', 'class' => 'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300'],
    };
@endphp

{{-- Verification is a defined process as of the case's review date, not a guarantee the transaction can't fail. --}}
<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $config['class'] }}"
      title="Reflects Distrax's verification process; not a guarantee against transaction failure.">
    <span class="h-1.5 w-1.5 rounded-full {{ $config['dot'] }}"></span>
    {{ $config['label'] }}
</span>
