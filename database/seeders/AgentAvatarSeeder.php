<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AgentAvatarSeeder extends Seeder
{
    /** @var list<string> */
    private const AVATARS = [
        'assets/images/website/agent/agent-1.webp',
        'assets/images/website/agent/agent-2.webp',
        'assets/images/website/agent/agent-3.webp',
        'assets/images/website/agent/agent-4.webp',
        'assets/images/website/agent/agent-5.webp',
    ];

    public function run(): void
    {
        $agents = Agent::query()
            ->with(['user:id,avatar'])
            ->withCount(['listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->where('is_active', true)
            ->whereHas('user', fn (Builder $query) => $query->whereNull('avatar'))
            ->orderByDesc('listings_count')
            ->orderBy('id')
            ->get(['id', 'user_id']);

        foreach ($agents as $index => $agent) {
            $sourcePath = self::AVATARS[$index % count(self::AVATARS)];
            $avatarPath = 'agents/seeded/'.File::basename($sourcePath);

            Storage::disk('public')->put(
                $avatarPath,
                File::get(public_path($sourcePath)),
            );

            $agent->user->update(['avatar' => $avatarPath]);
        }
    }
}
