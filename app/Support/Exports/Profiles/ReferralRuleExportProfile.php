<?php

namespace App\Support\Exports\Profiles;

use App\Models\ReferralRule;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReferralRuleExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'referral-rules';
    }

    public function permission(): string
    {
        return 'marketing.view';
    }

    public function title(): string
    {
        return 'Referral Rules';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'reward_type' => 'Reward Type',
            'reward_value' => 'Reward Value',
            'max_referrals' => 'Max Referrals',
            'min_purchase_amount' => 'Min Purchase',
            'is_active' => 'Active',
            'starts_at' => 'Starts At',
            'expires_at' => 'Expires At',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ReferralRuleController@index ordering.
     */
    public function query(Request $request): Builder
    {
        return ReferralRule::orderBy('reward_value');
    }

    /**
     * @param  ReferralRule  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'reward_type' => $row->reward_type,
            'reward_value' => $row->reward_value,
            'max_referrals' => $row->max_referrals,
            'min_purchase_amount' => $row->min_purchase_amount,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'starts_at' => $row->starts_at?->toDateTimeString(),
            'expires_at' => $row->expires_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
