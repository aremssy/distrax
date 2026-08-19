<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    /**
     * Execute $callback at most once per (user, scope, key) and durably persist its
     * response so repeated requests — even after a cache flush — replay the exact
     * same result instead of re-running the side effect (booking/payment creation).
     *
     * A cache lock serialises concurrent requests carrying the same key; the
     * database unique index on (user_id, scope, key) is the durable guarantee
     * of last resort — if two requests both slip past the lock (e.g. different
     * app servers without a shared lock store), the loser's insert fails and it
     * reads back the winner's row instead of returning its own duplicate result.
     *
     * @param  callable(): array{status: int, body: mixed}  $callback
     * @return array{status: int, body: mixed}
     */
    public function remember(User $user, string $scope, string $key, callable $callback): array
    {
        $existing = $this->find($user, $scope, $key);

        if ($existing) {
            return $existing;
        }

        $lock = Cache::lock("idempotency:lock:{$scope}:{$user->id}:{$key}", 30);
        $lock->block(10);

        try {
            $existing = $this->find($user, $scope, $key);

            if ($existing) {
                return $existing;
            }

            $result = $callback();

            // Only persist successful responses — a transient failure (e.g. gateway
            // timeout) should not permanently pin a retried request to that error.
            if ($result['status'] >= 400) {
                return $result;
            }

            try {
                IdempotencyKey::create([
                    'user_id' => $user->id,
                    'scope' => $scope,
                    'key' => $key,
                    'response_status' => $result['status'],
                    'response_body' => $result['body'],
                ]);
            } catch (QueryException $exception) {
                // Unique constraint race: another request already recorded a result.
                $existing = $this->find($user, $scope, $key);

                if (! $existing) {
                    throw $exception;
                }

                return $existing;
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{status: int, body: mixed}|null
     */
    private function find(User $user, string $scope, string $key): ?array
    {
        $record = IdempotencyKey::where('user_id', $user->id)
            ->where('scope', $scope)
            ->where('key', $key)
            ->first();

        if (! $record) {
            return null;
        }

        return ['status' => $record->response_status, 'body' => $record->response_body];
    }
}
