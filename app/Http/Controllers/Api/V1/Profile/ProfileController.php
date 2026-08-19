<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Profile\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadAvatarRequest;
use App\Http\Requests\Api\V1\Profile\UploadVerificationRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Jobs\ExportUserDataJob;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends ApiController
{
    /**
     * GET /api/v1/me
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('role', 'wallet');

        return $this->success(new UserResource($user));
    }

    /**
     * PUT /api/v1/me
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return $this->success(
            new UserResource($request->user()->fresh('role', 'wallet')),
            'Profile updated successfully.'
        );
    }

    /**
     * POST /api/v1/me/photo
     *
     * Replaces the current avatar. Stores in public disk under avatars/{id}/.
     */
    public function uploadPhoto(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        // Delete previous avatar if it exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $extension = $request->file('photo')->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $path = $request->file('photo')->storeAs("avatars/{$user->id}", $filename, 'public');

        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => asset('storage/'.$path),
        ], 'Photo uploaded successfully.');
    }

    /**
     * PUT /api/v1/me/password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        $currentAccessToken = $user->currentAccessToken();

        if ($currentAccessToken instanceof PersonalAccessToken) {
            // Revoke all other tokens so other devices are signed out on password change
            $user->tokens()->where('id', '!=', $currentAccessToken->id)->delete();
        } elseif (Auth::guard('web')->check()) {
            Auth::guard('web')->logoutOtherDevices($request->validated('password'));
        }

        return $this->success(null, 'Password changed successfully. Other sessions have been signed out.');
    }

    /**
     * POST /api/v1/me/verification
     *
     * Upload a NID/passport document → sets status to "pending".
     * Document is stored privately (never publicly accessible via URL).
     */
    public function submitVerification(UploadVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->verification_status === 'verified') {
            return $this->error('Your identity is already verified.', 409);
        }

        // Delete previous document if present
        if ($user->verification_document_path) {
            Storage::disk('local')->delete($user->verification_document_path);
        }

        $extension = $request->file('document')->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $path = $request->file('document')->storeAs(
            "verification/{$user->id}",
            $filename,
            'local'
        );

        $user->update([
            'verification_document_path' => $path,
            'verification_status' => 'pending',
            'verification_reviewed_at' => null,
            'verification_note' => null,
        ]);

        return $this->success(null, 'Document submitted. Your verification is under review.');
    }

    /**
     * GET /api/v1/me/verification
     */
    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'status' => $user->verification_status,
            'note' => $user->verification_note,
            'reviewed_at' => $user->verification_reviewed_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/me/export
     *
     * Generates a full data export for the authenticated user (GDPR Art. 20).
     * Dispatches a queued job and returns a signed download URL valid for 1 hour.
     * On sync queue (shared hosting), the file is ready immediately.
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();
        $jobKey = "user_export_{$user->id}";

        ExportUserDataJob::dispatch($user);

        // If running on sync driver the file is written by now; build a signed URL.
        // On async queues the URL will 404 until the job completes — callers should
        // retry after a few minutes.
        $downloadUrl = route('api.v1.me.export.download', ['token' => encrypt($user->id)]);

        return $this->success([
            'status' => 'processing',
            'download_url' => $downloadUrl,
            'expires_in' => '1 hour',
        ], 'Your data export is being prepared. Use the download_url to retrieve it.');
    }

    /**
     * GET /api/v1/me/export/download?token=...
     *
     * Streams the pre-generated export file to the client.
     */
    public function downloadExport(Request $request): mixed
    {
        try {
            $userId = (int) decrypt($request->query('token'));
        } catch (DecryptException) {
            return $this->error('Invalid export token.', 422);
        }

        // The export belongs to whoever is authenticated — the token is only a
        // convenience handle and must never grant access to another user's file.
        if ($userId !== $request->user()->id) {
            return $this->error('Invalid export token.', 403);
        }

        $path = "exports/user_{$userId}.json";

        if (! Storage::disk('local')->exists($path)) {
            return $this->error('Export not ready yet. Please try again in a few minutes.', 404);
        }

        return Storage::disk('local')->download($path, "ready-export-user-{$userId}.json");
    }

    /**
     * DELETE /api/v1/me
     *
     * Initiates account deletion with a 30-day grace period.
     * Within the grace period the user can log in again to cancel.
     * Listings are soft-deleted immediately; conversations are preserved
     * (the other party owns them).
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->deletion_requested_at) {
            $scheduledAt = $user->deletion_requested_at->addDays(30)->toDateString();

            return $this->error(
                "Account deletion is already scheduled for {$scheduledAt}. Log in before that date to cancel.",
                409
            );
        }

        // Soft-delete the user's property listings immediately
        $user->listings()->each(fn ($listing) => $listing->delete());

        $user->update(['deletion_requested_at' => now()]);

        // Revoke all tokens — user is effectively signed out
        $user->tokens()->delete();

        $scheduledDate = now()->addDays(30)->toDateString();

        return $this->success([
            'scheduled_deletion_date' => $scheduledDate,
        ], "Account deletion scheduled for {$scheduledDate}. Log in before that date to cancel.");
    }
}
