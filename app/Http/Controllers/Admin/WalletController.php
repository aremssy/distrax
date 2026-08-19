<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustWalletRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        return view('admin.wallets.index', [
            'wallets' => Wallet::with('user:id,name,email')->latest()->paginate(25),
        ]);
    }

    public function show(Wallet $wallet)
    {
        return view('admin.wallets.show', [
            'wallet' => $wallet->load('user:id,name,email'),
            'transactions' => $wallet->transactions()->latest()->paginate(25),
        ]);
    }

    public function adjust(AdjustWalletRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $user): void {
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => setting('default_currency', 'BDT'), 'balance' => 0]
            );

            // Re-read the row under a write lock so concurrent adjustments serialize —
            // otherwise two debits can both read the same balance and overspend.
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if ($data['type'] === 'debit' && $wallet->balance < $data['amount']) {
                abort(422, 'Wallet balance is too low for this debit.');
            }

            $newBalance = $data['type'] === 'credit'
                ? $wallet->balance + $data['amount']
                : $wallet->balance - $data['amount'];

            $wallet->update(['balance' => $newBalance]);
            $wallet->transactions()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'balance_after' => $newBalance,
                'description' => $data['description'] ?? 'Admin wallet adjustment',
            ]);
        });

        return redirect()->route('admin.wallets.index')->with('success', 'Wallet adjusted successfully.');
    }
}
