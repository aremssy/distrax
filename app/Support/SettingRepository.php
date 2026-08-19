<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingRepository
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Setting keys holding sensitive credentials. Their values are encrypted at rest
     * and never rendered back to the browser; the `setting()` helper still returns the
     * decrypted plaintext to the gateway drivers.
     *
     * @var list<string>
     */
    private const SECRET_KEYS = [
        'stripe_secret_key', 'stripe_webhook_secret',
        'paypal_client_secret',
        'bkash_password', 'bkash_app_secret',
        'razorpay_key_secret', 'razorpay_webhook_secret',
        'paystack_secret_key',
        'mail_password',
        'pusher_secret',
    ];

    /** @return list<string> */
    public static function secretKeys(): array
    {
        return self::SECRET_KEYS;
    }

    public static function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $this->encode($key, $value)]);
        $this->flush();
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $this->encode($key, $value)]);
        }
        $this->flush();
    }

    /** Encrypt secret values before they are stored; store everything else verbatim. */
    private function encode(string $key, mixed $value): string
    {
        $value = (string) $value;

        return self::isSecret($key) && $value !== ''
            ? Crypt::encryptString($value)
            : $value;
    }

    /** Decrypt a stored secret, tolerating any legacy plaintext value. */
    private function decodeSecret(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return $stored; // pre-encryption plaintext
        }
    }

    public function flush(): void
    {
        // Forget through the memoized store so the in-request copy is dropped too.
        Cache::memo()->forget(self::CACHE_KEY);
    }

    public function all(): Collection
    {
        // Store as plain array — DatabaseStore has serializable_classes=false
        // which blocks PHP objects during unserialize. Memoized so the dozens of
        // setting() calls per request cost one cache round-trip, not dozens.
        return collect(
            Cache::memo()->rememberForever(self::CACHE_KEY, fn () => $this->loadArray())
        );
    }

    /** @return array<string, mixed> */
    private function loadArray(): array
    {
        return Setting::all()->mapWithKeys(function (Setting $s): array {
            $value = match (true) {
                self::isSecret($s->key) => $this->decodeSecret($s->value),
                $s->type === 'boolean' => filter_var($s->value, FILTER_VALIDATE_BOOLEAN),
                $s->type === 'integer' => (int) $s->value,
                $s->type === 'json' => json_decode($s->value, true),
                default => $s->value,
            };

            return [$s->key => $value];
        })->all();
    }
}
