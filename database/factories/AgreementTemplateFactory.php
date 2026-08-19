<?php

namespace Database\Factories;

use App\Models\AgreementTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgreementTemplate>
 */
class AgreementTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => 'Standard Tenancy Agreement',
            'body' => 'This agreement is made between {{owner_name}} and {{tenant_name}} for the rental of {{property_title}} at a monthly rent of {{rent_amount}}, starting {{start_date}}.',
            'is_default' => true,
        ];
    }
}
