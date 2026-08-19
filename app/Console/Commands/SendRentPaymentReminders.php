<?php

namespace App\Console\Commands;

use App\Models\RentPayment;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\PostEntitlementService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rent:send-payment-reminders')]
#[Description('Flag overdue rent payments and notify tenants/owners whose plan unlocks rent reminders.')]
class SendRentPaymentReminders extends Command
{
    public function handle(PostEntitlementService $entitlement, NotificationDispatcher $dispatcher): int
    {
        $this->flagOverdue();

        // The due set grows with the tenancy book — stream it in id-ordered batches
        // instead of hydrating every row. lazy() (not cursor()) because the loop
        // needs the eager-loaded tenancy graph; the loop writes notifications, never
        // the rent_payments rows it iterates, so offset-based paging stays stable.
        $due = RentPayment::query()
            ->whereIn('status', ['pending', 'overdue'])
            ->where(function ($query) {
                $query->where('status', 'overdue')
                    ->orWhereBetween('due_date', [now()->toDateString(), now()->addDays(3)->toDateString()]);
            })
            ->with(['tenancy.owner', 'tenancy.tenant', 'tenancy.listing'])
            ->lazy(200);

        $sent = 0;

        foreach ($due as $payment) {
            $owner = $payment->tenancy?->owner;

            if (! $owner || ! in_array('rent_reminders', $entitlement->unlockedFeatures($owner), true)) {
                continue;
            }

            $this->notifyForPayment($payment, $owner, $dispatcher);
            $sent++;
        }

        $this->info("Sent {$sent} rent reminder notification set(s).");

        return self::SUCCESS;
    }

    private function notifyForPayment(RentPayment $payment, User $owner, NotificationDispatcher $dispatcher): void
    {
        $isOverdue = $payment->status === 'overdue';
        $listingTitle = $payment->tenancy->listing?->title ?? 'your property';
        $dueDate = $payment->due_date->toFormattedDateString();
        $title = $isOverdue ? 'Rent payment overdue' : 'Upcoming rent payment';

        $dispatcher->send(
            $owner,
            'rent_reminder',
            $title,
            $isOverdue
                ? "Rent of {$payment->amount} for {$listingTitle} was due on {$dueDate} and is still unpaid."
                : "Rent of {$payment->amount} for {$listingTitle} is due on {$dueDate}.",
            ['rent_payment_id' => $payment->id],
            $payment
        );

        $tenant = $payment->tenancy->tenant;

        if ($tenant) {
            $dispatcher->send(
                $tenant,
                'rent_reminder',
                $title,
                $isOverdue
                    ? "Your rent of {$payment->amount} for {$listingTitle} was due on {$dueDate} and is still unpaid."
                    : "Your rent of {$payment->amount} for {$listingTitle} is due on {$dueDate}.",
                ['rent_payment_id' => $payment->id],
                $payment
            );
        }
    }

    private function flagOverdue(): void
    {
        RentPayment::query()
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }
}
