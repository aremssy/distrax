<?php

namespace App\Http\Controllers\Admin;

use App\Events\VerificationResulted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVerificationRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('verificationReviewer:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('verification_status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->whereIn('verification_status', ['pending', 'verified', 'rejected'])
            ->latest('verification_reviewed_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verification.index', compact('users'));
    }

    /**
     * Stream the user's identity document from the private disk so admins can
     * actually review what they are approving. Never exposed via a public URL.
     */
    public function document(Request $request, User $user): StreamedResponse
    {
        // A lower-tier admin must never reach a super admin's private ID document.
        abort_unless($request->user()->canManageUser($user), 403);

        abort_unless(
            $user->verification_document_path
            && Storage::disk('local')->exists($user->verification_document_path),
            404
        );

        return Storage::disk('local')->response($user->verification_document_path);
    }

    public function update(UpdateVerificationRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        // Same ceiling as the rest of user management: a non-super-admin may not act on a
        // super admin's account, so they cannot flip a super admin's verification status.
        abort_unless($request->user()->canManageUser($user), 403);

        $data = $request->validated();

        // The verification decision and its audit entry must land together — an
        // audit trail with no decision (or a decision with no audit entry) is a
        // compliance hole. The notification event stays outside the transaction so
        // e-mail/SMS is never sent for a decision that ended up rolled back.
        DB::transaction(function () use ($audit, $data, $user): void {
            $user->update([
                'verification_status' => $data['status'],
                'verification_reviewed_by' => auth()->id(),
                'verification_note' => $data['note'] ?? null,
                'verification_reviewed_at' => now(),
            ]);

            $audit->record('verification.'.$data['status'], $user, ['note' => $data['note'] ?? null]);
        });

        if (in_array($data['status'], ['verified', 'rejected'], true)) {
            VerificationResulted::dispatch($user, $data['status'], $data['note'] ?? null);
        }

        return redirect()->route('admin.verification.index')->with('success', 'User verification updated.');
    }
}
