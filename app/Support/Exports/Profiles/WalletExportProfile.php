<?php

namespace App\Support\Exports\Profiles;

use App\Models\Wallet;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WalletExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'wallets';
    }

    public function permission(): string
    {
        return 'payments.view';
    }

    public function title(): string
    {
        return 'Wallets';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'user' => 'User',
            'balance' => 'Balance',
            'currency' => 'Currency',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors WalletController@index.
     */
    public function query(Request $request): Builder
    {
        return Wallet::with('user:id,name,email')->latest();
    }

    /**
     * @param  Wallet  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'user' => $row->user?->name,
            'balance' => $row->balance,
            'currency' => $row->currency,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
