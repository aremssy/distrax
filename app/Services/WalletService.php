<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function findOrCreateForUser(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => setting('default_currency', 'BDT')]
        );
    }

    /**
     * Credit the wallet atomically.
     */
    public function credit(Wallet $wallet, int $amount, string $description, ?Model $transactionable = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $transactionable): WalletTransaction {
            $locked = Wallet::lockForUpdate()->find($wallet->id);
            $locked->increment('balance', $amount);
            $locked->refresh();

            return WalletTransaction::create([
                'wallet_id' => $locked->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => (int) $locked->balance,
                'description' => $description,
                'transactionable_type' => $transactionable?->getMorphClass(),
                'transactionable_id' => $transactionable?->id,
            ]);
        });
    }

    /**
     * Debit the wallet atomically. Throws RuntimeException on insufficient balance.
     */
    public function debit(Wallet $wallet, int $amount, string $description, ?Model $transactionable = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $transactionable): WalletTransaction {
            $locked = Wallet::lockForUpdate()->find($wallet->id);

            if ((int) $locked->balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $locked->decrement('balance', $amount);
            $locked->refresh();

            return WalletTransaction::create([
                'wallet_id' => $locked->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => (int) $locked->balance,
                'description' => $description,
                'transactionable_type' => $transactionable?->getMorphClass(),
                'transactionable_id' => $transactionable?->id,
            ]);
        });
    }
}
