<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\LedgerTransaction;
use App\Models\PropertyListing;
use App\Models\RentPayment;
use App\Models\RentReceipt;
use App\Models\SubscriptionPlan;
use App\Models\Tenancy;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds a realistic rent-management lifecycle for a demo landlord: an active
 * subscription that unlocks the feature, units, tenancies, a signed agreement,
 * a run of paid rent invoices plus one due and one overdue, receipts for the
 * paid months, and ledger income/expense entries.
 *
 * Idempotent: it targets one demo landlord and clears that landlord's prior
 * rent-management rows before reseeding.
 */
class RentManagementSeeder extends Seeder
{
    public function run(): void
    {
        $landlord = $this->resolveLandlord();

        if (! $landlord) {
            $this->command?->warn('RentManagementSeeder skipped: no owner with active rent listings found.');

            return;
        }

        $this->clearExisting($landlord);
        $this->grantRentPlan($landlord);

        $template = $this->seedAgreementTemplate($landlord);
        $listings = PropertyListing::where('owner_id', $landlord->id)
            ->where('type', 'rent')
            ->orderBy('id')
            ->limit(4)
            ->get();

        $tenantPool = User::where('id', '!=', $landlord->id)->inRandomOrder()->limit(12)->get()->values();
        $today = Carbon::today();

        foreach ($listings as $index => $listing) {
            $this->seedTenancyLifecycle($landlord, $listing, $tenantPool, $template, $index, $today);
        }

        $this->command?->info('Rent-management lifecycle seeded for '.$landlord->name.'.');
    }

    private function resolveLandlord(): ?User
    {
        $ownerId = PropertyListing::where('type', 'rent')
            ->where('status', 'active')
            ->select('owner_id')
            ->groupBy('owner_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('owner_id');

        return $ownerId ? User::find($ownerId) : null;
    }

    private function clearExisting(User $landlord): void
    {
        $tenancyIds = Tenancy::withTrashed()->where('owner_id', $landlord->id)->pluck('id');

        RentReceipt::whereIn('tenancy_id', $tenancyIds)->delete();
        RentPayment::whereIn('tenancy_id', $tenancyIds)->delete();
        Agreement::whereIn('tenancy_id', $tenancyIds)->delete();
        LedgerTransaction::where('owner_id', $landlord->id)->delete();
        $ownedListingIds = PropertyListing::where('owner_id', $landlord->id)->pluck('id');
        Unit::whereIn('property_listing_id', $ownedListingIds)->forceDelete();
        Tenancy::withTrashed()->where('owner_id', $landlord->id)->forceDelete();
        AgreementTemplate::where('owner_id', $landlord->id)->forceDelete();
    }

    private function grantRentPlan(User $landlord): void
    {
        $plan = SubscriptionPlan::whereRaw("JSON_EXTRACT(unlocked_features, '$.multi_unit') = true")
            ->orderByDesc('price')
            ->first()
            ?? SubscriptionPlan::orderByDesc('price')->first();

        if (! $plan) {
            return;
        }

        UserSubscription::where('user_id', $landlord->id)->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        UserSubscription::create([
            'user_id' => $landlord->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now()->subMonths(6),
            'ends_at' => now()->addMonths(6),
            'status' => 'active',
            'posts_used' => 0,
            'auto_renew' => true,
        ]);
    }

    private function seedAgreementTemplate(User $landlord): AgreementTemplate
    {
        return AgreementTemplate::create([
            'owner_id' => $landlord->id,
            'name' => 'Standard Residential Tenancy Agreement',
            'body' => "This tenancy agreement is made between the landlord and {{tenant_name}} for the property at {{property_title}}.\n\n".
                'Monthly rent: {{rent_amount}}. Security deposit: {{deposit_amount}}. '.
                'Rent is payable by the {{due_day}} of each month. The tenant agrees to maintain the property in good condition '.
                'and provide one month notice before vacating.',
            'is_default' => true,
        ]);
    }

    private function seedTenancyLifecycle(User $landlord, PropertyListing $listing, $tenantPool, AgreementTemplate $template, int $index, Carbon $today): void
    {
        $tenant = $tenantPool[$index % max(1, $tenantPool->count())] ?? null;
        $rent = (int) $listing->price;
        $startDate = $today->copy()->subMonths(5)->startOfMonth();
        $dueDay = 5;

        // The busiest listing gets multiple units to exercise the multi-unit tools.
        $unit = null;
        if ($index === 0) {
            $unit = Unit::create([
                'property_listing_id' => $listing->id,
                'name' => 'Unit 3B',
                'floor' => '3',
                'rent_amount' => $rent,
                'status' => 'occupied',
            ]);

            Unit::create([
                'property_listing_id' => $listing->id,
                'name' => 'Unit 4A',
                'floor' => '4',
                'rent_amount' => (int) round($rent * 1.1),
                'status' => 'vacant',
            ]);
        }

        $tenancy = Tenancy::create([
            'owner_id' => $landlord->id,
            'property_listing_id' => $listing->id,
            'unit_id' => $unit?->id,
            'tenant_id' => $tenant?->id,
            'tenant_name' => $tenant?->name ?? 'Rahim Uddin',
            'tenant_phone' => '+1415'.random_int(2000000, 9999999),
            'tenant_email' => $tenant?->email ?? 'tenant'.$listing->id.'@example.com',
            'rent_amount' => $rent,
            'deposit_amount' => $rent * 2,
            'due_day_of_month' => $dueDay,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addYear(),
            'status' => 'active',
        ]);

        Agreement::create([
            'tenancy_id' => $tenancy->id,
            'agreement_template_id' => $template->id,
            'content' => str_replace(
                ['{{tenant_name}}', '{{property_title}}', '{{rent_amount}}', '{{deposit_amount}}', '{{due_day}}'],
                [$tenancy->tenant_name, $listing->title, money($rent), money($rent * 2), $dueDay],
                $template->body,
            ),
            'status' => 'finalized',
            'generated_at' => $startDate,
        ]);

        $this->seedPaymentsAndLedger($landlord, $listing, $tenancy, $rent, $startDate, $dueDay, $today);
    }

    private function seedPaymentsAndLedger(User $landlord, PropertyListing $listing, Tenancy $tenancy, int $rent, Carbon $startDate, int $dueDay, Carbon $today): void
    {
        // Five months from the tenancy start: the earliest are paid, then one due
        // this month, and (for the first two tenancies) one overdue.
        for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
            $periodStart = $startDate->copy()->addMonths($monthOffset)->startOfMonth();

            if ($periodStart->greaterThan($today->copy()->endOfMonth())) {
                break;
            }

            $dueDate = $periodStart->copy()->day($dueDay);
            $isCurrentMonth = $periodStart->isSameMonth($today);
            $status = 'paid';
            $paidAt = null;

            if ($isCurrentMonth) {
                $status = 'pending';
            } elseif ($dueDate->lessThan($today)) {
                $status = 'paid';
                $paidAt = $dueDate->copy()->addDays(random_int(0, 3));
            }

            // Leave the most recent completed month overdue on the busiest tenancy.
            if (! $isCurrentMonth && $periodStart->isSameMonth($today->copy()->subMonth()) && $tenancy->id % 2 === 0) {
                $status = 'overdue';
                $paidAt = null;
            }

            $payment = RentPayment::create([
                'tenancy_id' => $tenancy->id,
                'period_start' => $periodStart,
                'period_end' => $periodStart->copy()->endOfMonth(),
                'due_date' => $dueDate,
                'amount' => $rent,
                'status' => $status,
                'paid_at' => $paidAt,
            ]);

            if ($status === 'paid') {
                RentReceipt::create([
                    'rent_payment_id' => $payment->id,
                    'tenancy_id' => $tenancy->id,
                    'receipt_number' => 'RCPT-'.$paidAt->format('Ymd').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
                    'amount' => $rent,
                    'issued_at' => $paidAt,
                    'notes' => 'Rent for '.$periodStart->format('F Y'),
                ]);

                LedgerTransaction::create([
                    'owner_id' => $landlord->id,
                    'property_listing_id' => $listing->id,
                    'tenancy_id' => $tenancy->id,
                    'type' => 'income',
                    'category' => 'rent',
                    'amount' => $rent,
                    'occurred_on' => $paidAt,
                    'description' => 'Rent received — '.$periodStart->format('F Y'),
                ]);
            }
        }

        // A couple of maintenance/service expenses so the income/expense summary is realistic.
        foreach ([['maintenance', 'Plumbing repair', 0.04], ['utilities', 'Common area electricity', 0.02]] as [$category, $desc, $ratio]) {
            LedgerTransaction::create([
                'owner_id' => $landlord->id,
                'property_listing_id' => $listing->id,
                'tenancy_id' => $tenancy->id,
                'type' => 'expense',
                'category' => $category,
                'amount' => (int) round($rent * $ratio),
                'occurred_on' => $today->copy()->subDays(random_int(5, 40)),
                'description' => $desc,
            ]);
        }
    }
}
