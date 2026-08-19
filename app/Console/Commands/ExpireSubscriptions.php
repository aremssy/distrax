<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:expire')]
#[Description('Expire active subscriptions whose end date has passed.')]
class ExpireSubscriptions extends Command
{
    public function handle(): int
    {
        $expired = UserSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
