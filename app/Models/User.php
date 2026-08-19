<?php

namespace App\Models;

use App\Traits\HasPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'role_id', 'verification_status', 'verification_document_path', 'verification_reviewed_by', 'verification_note', 'verification_reviewed_at', 'language', 'currency', 'country_code', 'timezone', 'avatar', 'is_blocked', 'social_provider', 'social_id', 'last_active_at', 'phone_visibility', 'deletion_requested_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPermissions, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_reviewed_at' => 'datetime',
            'last_active_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(PropertyListing::class, 'owner_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)->where('status', 'active')->latestOfMany();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function compares(): HasMany
    {
        return $this->hasMany(Compare::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'starter_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class, 'owner_id');
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function technicianProfile(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(Block::class, 'blocked_id');
    }

    public function verificationReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verification_reviewed_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }
}
