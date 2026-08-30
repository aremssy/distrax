<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\User;
use App\Models\VerificationCase;
use App\Models\VerificationEvidence;
use App\Models\VerificationScore;
use App\Models\VerificationTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns the verification-case state machine: opening a case, routing/recording task
 * outcomes, and finalizing into one of the five badge states (see VerificationCase
 * status enum). Every write goes through AuditLogger so it shows up in the same
 * AuditLogController the admin already uses.
 */
class VerificationCaseService
{
    /** Ordered layers a case walks through; final_decision must always run last. */
    public const LAYERS = [
        'seller_kyc', 'document_review', 'title', 'survey', 'physical',
        'ownership_authority', 'encumbrance', 'litigation', 'planning', 'final_decision',
    ];

    /** Which staff role is responsible for each layer's outcome. */
    public const OWNER_ROLE_MAP = [
        'seller_kyc' => 'distrax',
        'document_review' => 'distrax',
        'final_decision' => 'distrax',
        'title' => 'legal',
        'ownership_authority' => 'legal',
        'encumbrance' => 'legal',
        'litigation' => 'legal',
        'survey' => 'licensed_surveyor',
        'planning' => 'surveyor_planning_professional',
        'physical' => 'distrax_inspector',
    ];

    public function __construct(
        private AuditLogger $audit,
        private NotificationCenter $notifications,
        private PropertyTimelineService $timeline,
        private IntelligenceService $intelligence,
    ) {}

    /** Open a case with one task per layer. No-op if the listing already has an open case. */
    public function openCase(PropertyListing $listing): VerificationCase
    {
        $existing = $listing->verificationCases()->whereNotIn('status', ['not_verified'])->latest()->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($listing): VerificationCase {
            $case = VerificationCase::create([
                'property_listing_id' => $listing->id,
                'status' => 'in_progress',
                'opened_at' => now(),
            ]);

            foreach (self::LAYERS as $layer) {
                VerificationTask::create([
                    'verification_case_id' => $case->id,
                    'layer' => $layer,
                    'owner_role' => self::OWNER_ROLE_MAP[$layer],
                    'status' => 'not_started',
                ]);
            }

            $listing->update(['verification_case_id' => $case->id]);

            $this->audit->record('verification.case_opened', $listing, ['case_id' => $case->id]);

            return $case;
        });
    }

    public function assignOfficer(VerificationCase $case, User $officer, User $actor): void
    {
        $case->update(['assigned_officer_id' => $officer->id]);

        $this->audit->record('verification.case_assigned', $case->listing, [
            'case_id' => $case->id,
            'officer_id' => $officer->id,
            'assigned_by' => $actor->id,
        ]);
    }

    /**
     * Record a task's outcome. Passing $waived=true requires $notes to carry the waiver
     * reason and lets a failed/flagged layer count as passed for finalize's gate check.
     */
    public function recordTaskOutcome(VerificationTask $task, string $status, ?string $notes, User $actor, bool $waived = false): VerificationTask
    {
        if (! in_array($status, ['not_started', 'in_progress', 'passed', 'failed', 'flagged'], true)) {
            throw new RuntimeException("Invalid task status: {$status}");
        }

        if ($waived && ($status !== 'passed' || ! $notes)) {
            throw new RuntimeException('A waiver requires status=passed and a reason in notes.');
        }

        $task->update([
            'status' => $status,
            'notes' => $notes,
            'completed_at' => in_array($status, ['passed', 'failed', 'flagged'], true) ? now() : null,
        ]);

        $this->audit->record('verification.task_'.$status, $task->case->listing, [
            'task_id' => $task->id,
            'layer' => $task->layer,
            'waived' => $waived,
            'notes' => $notes,
            'actor_id' => $actor->id,
        ]);

        if ($task->layer === 'final_decision' && in_array($status, ['passed', 'failed'], true)) {
            $this->finalize($task->case->load('listing'), $status, $actor);
        }

        return $task->fresh();
    }

    public function attachEvidence(VerificationTask $task, string $type, UploadedFile $file, ?string $description, User $actor): VerificationEvidence
    {
        $path = $file->store('verification-evidence', 'local');

        $evidence = VerificationEvidence::create([
            'verification_task_id' => $task->id,
            'type' => $type,
            'file_path' => $path,
            'description' => $description,
            'uploaded_by' => $actor->id,
        ]);

        $this->audit->record('verification.evidence_uploaded', $task->case->listing, [
            'task_id' => $task->id,
            'evidence_id' => $evidence->id,
        ]);

        return $evidence;
    }

    /**
     * final_decision can only pass once every other task is passed (or explicitly
     * waived, which is stored as passed + a notes reason). Called automatically by
     * recordTaskOutcome() when the final_decision task settles.
     */
    private function finalize(VerificationCase $case, string $finalDecision, User $actor): void
    {
        $otherTasks = $case->tasks()->where('layer', '!=', 'final_decision')->get();

        if ($finalDecision === 'passed' && $otherTasks->contains(fn (VerificationTask $t) => $t->status !== 'passed')) {
            throw new RuntimeException('final_decision cannot pass while other layers are unresolved. Waive or resolve them first.');
        }

        $listing = $case->listing;

        if ($finalDecision === 'passed') {
            $hasDisclosures = $listing->disclosures()->exists();
            $case->update([
                'status' => $hasDisclosures ? 'disclosure_required' : 'distrax_verified',
                'closed_at' => now(),
                'expiry_review_date' => now()->addYear()->toDateString(),
            ]);

            $this->generateScore($case, $otherTasks);
        } else {
            $failedTasks = $otherTasks->where('status', 'failed');
            $legalOnly = $failedTasks->isNotEmpty() && $failedTasks->every(
                fn (VerificationTask $t) => $t->owner_role === 'legal'
            );

            $case->update([
                'status' => $legalOnly ? 'under_legal_review' : 'not_verified',
                'closed_at' => $legalOnly ? null : now(),
            ]);
        }

        $this->audit->record('verification.case_'.$case->status, $listing, ['case_id' => $case->id]);

        if ($case->status === 'distrax_verified') {
            $this->timeline->event($listing, 'verification_completed', 'Verification completed', 'public');
        }

        // Deal Score is verification-sensitive: reflects the badge in the dedicated component.
        $this->intelligence->recompute($listing);

        $this->notifications->announce(
            type: 'verification_completed',
            adminTitle: "Verification case #{$case->id} resolved: {$case->status}",
            user: $listing->owner,
            userTitle: match ($case->status) {
                'distrax_verified' => 'Your listing is now Distrax Verified',
                'disclosure_required' => 'Verification passed \u2014 disclosures required before publishing',
                'under_legal_review' => 'Your listing verification needs legal review',
                default => 'Your listing did not pass verification',
            },
            body: $listing->title,
            subject: $listing,
            permission: 'verification_cases.view',
        );
    }

    /** @param  \Illuminate\Support\Collection<int, VerificationTask>  $tasks */
    private function generateScore(VerificationCase $case, $tasks): VerificationScore
    {
        $byLayer = $tasks->keyBy('layer');
        $passed = $tasks->where('status', 'passed')->count();
        $score = $tasks->isEmpty() ? 0 : round(($passed / $tasks->count()) * 100, 2);
        $reference = 'DTX-VER-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        while (VerificationScore::where('reference_id', $reference)->exists()) {
            $reference = 'DTX-VER-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }

        return VerificationScore::create([
            'verification_case_id' => $case->id,
            'property_listing_id' => $case->property_listing_id,
            'reference_id' => $reference,
            'score' => $score,
            'seller_verification_status' => $byLayer->get('seller_kyc')?->status,
            'title_status' => $byLayer->get('title')?->status,
            'ownership_status' => $byLayer->get('ownership_authority')?->status,
            'survey_status' => $byLayer->get('survey')?->status,
            'physical_inspection_status' => $byLayer->get('physical')?->status,
            'legal_review_status' => $byLayer->get('litigation')?->status,
            'planning_status' => $byLayer->get('planning')?->status,
            'disclosure_count' => $case->listing->disclosures()->count(),
            'verification_date' => now(),
            'expiry_review_date' => $case->expiry_review_date,
            'qr_code_url' => '/verify/'.$reference,
        ]);
    }
}
