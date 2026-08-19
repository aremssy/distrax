<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Tenancy;

class AgreementGenerator
{
    public function generate(Tenancy $tenancy, ?AgreementTemplate $template): Agreement
    {
        $variables = [
            'owner_name' => $tenancy->owner?->name,
            'tenant_name' => $tenancy->tenantName(),
            'property_title' => $tenancy->listing?->title,
            'rent_amount' => $tenancy->rent_amount,
            'deposit_amount' => $tenancy->deposit_amount,
            'start_date' => $tenancy->start_date?->toDateString(),
            'end_date' => $tenancy->end_date?->toDateString(),
        ];

        $content = $template
            ? $template->render($variables)
            : "Tenancy agreement for {$variables['tenant_name']} — rent {$variables['rent_amount']} starting {$variables['start_date']}.";

        return Agreement::create([
            'tenancy_id' => $tenancy->id,
            'agreement_template_id' => $template?->id,
            'content' => $content,
            'status' => 'draft',
            'generated_at' => now(),
        ]);
    }
}
