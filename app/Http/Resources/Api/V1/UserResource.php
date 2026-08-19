<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar
                ? asset('storage/'.$this->avatar)
                : null,

            // Role & access
            'role' => $this->role?->name,

            // Verified badge — true only when admin has approved the document
            'is_verified' => $this->verification_status === 'verified',
            'verification_status' => $this->verification_status,

            // Localisation preferences
            'language' => $this->language,
            'currency' => $this->currency,

            // Privacy
            'phone_visibility' => $this->phone_visibility,

            // Account state
            'is_blocked' => $this->is_blocked,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),

            // Pending deletion (grace-period awareness on the client)
            'deletion_requested_at' => $this->deletion_requested_at?->toIso8601String(),

            // Wallet balance — only loaded when the relation is eager-loaded
            'wallet_balance' => $this->whenLoaded('wallet', fn () => $this->wallet?->balance),
        ];

        // NOTE: password, remember_token, social_id, verification_document_path,
        // verification_reviewed_by, verification_note, verification_reviewed_at
        // are intentionally excluded.
    }
}
