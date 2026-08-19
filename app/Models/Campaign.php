<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['created_by', 'name', 'channel', 'email_template_id', 'subject', 'content', 'target_segments', 'sent_to_user_ids', 'sent_count', 'status', 'scheduled_at', 'sent_at'])]
class Campaign extends Model
{
    /**
     * Audience segments a campaign can target, keyed by the value stored in
     * `target_segments`. Single source of truth for the admin form, the
     * request validation and CampaignSender's audience resolution.
     *
     * @var array<string, string>
     */
    public const SEGMENTS = [
        'all_users' => 'All Users',
        'verified_users' => 'Verified Users',
        'subscribed_users' => 'Subscribed Users',
        'property_owners' => 'Property Owners',
        'tenants' => 'Tenants',
        'technicians' => 'Technicians',
        'agents' => 'Agents',
    ];

    protected function casts(): array
    {
        return [
            'target_segments' => 'array',
            'sent_to_user_ids' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
