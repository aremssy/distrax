<?php

namespace App\Console\Commands;

use App\Models\VerificationCase;
use App\Models\VerificationTask;
use App\Services\VerificationCaseService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('verification:flag-expiring')]
#[Description('Create a re-review task for verified cases past their expiry_review_date. Never silently downgrades the badge.')]
class FlagExpiringVerifications extends Command
{
    public function handle(): int
    {
        $flagged = 0;

        VerificationCase::whereIn('status', ['distrax_verified', 'disclosure_required'])
            ->whereNotNull('expiry_review_date')
            ->where('expiry_review_date', '<=', now()->toDateString())
            ->whereDoesntHave('tasks', fn ($q) => $q->where('layer', 'final_decision')->where('status', 'not_started'))
            ->with('listing:id,title')
            ->chunkById(200, function ($cases) use (&$flagged): void {
                foreach ($cases as $case) {
                    VerificationTask::create([
                        'verification_case_id' => $case->id,
                        'layer' => 'final_decision',
                        'owner_role' => VerificationCaseService::OWNER_ROLE_MAP['final_decision'],
                        'status' => 'not_started',
                        'assigned_to' => $case->assigned_officer_id,
                        'notes' => 'Re-review required: verification expired on '.$case->expiry_review_date->toDateString(),
                    ]);

                    $flagged++;
                }
            });

        $this->info("{$flagged} verification case(s) flagged for re-review.");

        return self::SUCCESS;
    }
}
