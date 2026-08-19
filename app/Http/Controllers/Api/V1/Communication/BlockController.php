<?php

namespace App\Http\Controllers\Api\V1\Communication;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Block;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends ApiController
{
    /**
     * GET /api/v1/blocks
     *
     * List users the authenticated user has blocked.
     */
    public function index(Request $request): JsonResponse
    {
        $blocks = Block::where('blocker_id', $request->user()->id)
            ->with('blocked:id,name,avatar')
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'id' => $b->blocked->id,
                'name' => $b->blocked->name,
                'avatar' => $b->blocked->avatar ? asset('storage/'.$b->blocked->avatar) : null,
                'blocked_at' => $b->created_at->toIso8601String(),
            ]);

        return $this->success($blocks);
    }

    /**
     * POST /api/v1/block/{user}
     *
     * Block a user. Idempotent — silently returns 200 if already blocked.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return $this->error('You cannot block yourself.', 422);
        }

        Block::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $user->id,
        ]);

        return $this->success(null, 'User blocked.');
    }

    /**
     * DELETE /api/v1/block/{user}
     *
     * Unblock a user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $deleted = Block::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        if (! $deleted) {
            return $this->error('This user is not in your block list.', 404);
        }

        return $this->success(null, 'User unblocked.');
    }
}
