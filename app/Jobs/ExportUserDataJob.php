<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Collects a user's personal data and writes it as JSON to private storage.
 *
 * On ShouldQueue: when running on the 'sync' driver (shared hosting), this
 * executes immediately inside the request. With a real queue worker it runs
 * in the background. The download endpoint checks for the file before serving.
 */
class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly User $user) {}

    public function handle(): void
    {
        Storage::disk('local')->put(
            "exports/user_{$this->user->id}.json",
            json_encode(self::dataFor($this->user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Build the exportable copy of a user's personal data.
     *
     * @return array<string, mixed>
     */
    public static function dataFor(User $user): array
    {
        $user->loadMissing([
            'role',
            'wallet.transactions',
            'listings',
            'favorites',
            'savedSearches',
            'reviews',
            'notificationPreferences',
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'language' => $user->language,
                'currency' => $user->currency,
                'phone_visibility' => $user->phone_visibility,
                'role' => $user->role?->name,
                'verification_status' => $user->verification_status,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'wallet' => [
                'balance' => $user->wallet?->balance,
                'currency' => $user->wallet?->currency,
                'transactions' => $user->wallet?->transactions->map(fn ($t) => [
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'note' => $t->note,
                    'created_at' => $t->created_at->toIso8601String(),
                ])->all(),
            ],
            'listings' => $user->listings->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'status' => $l->status,
                'created_at' => $l->created_at->toIso8601String(),
            ])->all(),
            'favorites' => $user->favorites->pluck('property_listing_id')->all(),
            'saved_searches' => $user->savedSearches->map(fn ($s) => [
                'name' => $s->name,
                'filters' => $s->filters,
            ])->all(),
            'notification_preferences' => $user->notificationPreferences->map(fn ($p) => [
                'event' => $p->event,
                'channel' => $p->channel,
                'enabled' => $p->enabled,
            ])->all(),
        ];
    }
}
