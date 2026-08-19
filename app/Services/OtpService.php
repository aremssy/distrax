<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    /** OTP TTL in minutes. */
    private const TTL_MINUTES = 10;

    /** Resend cooldown in seconds. */
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(private SmsSender $sms) {}

    /**
     * Generate an OTP for the given phone+type, persist it, and dispatch via the
     * configured driver. Returns the plain-text code (useful in tests/log driver).
     */
    public function send(string $phone, string $type): string
    {
        $code = $this->generateCode();

        // Invalidate any previous unused OTPs of the same type
        Otp::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();

        Otp::create([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $this->dispatch($phone, $code);

        return $code;
    }

    /**
     * Verify an OTP. Returns true and marks it used on success; false otherwise.
     * Does NOT throw — the caller decides how to respond.
     */
    public function verify(string $phone, string $code, string $type): bool
    {
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Returns true if the phone+type combination can receive a new OTP
     * (i.e. the resend cooldown has passed).
     */
    public function canResend(string $phone, string $type): bool
    {
        return ! RateLimiter::tooManyAttempts(
            "otp_resend:{$type}:{$phone}",
            1
        );
    }

    /** Record a send so that canResend() enforces the cooldown. */
    public function recordSend(string $phone, string $type): void
    {
        RateLimiter::hit(
            "otp_resend:{$type}:{$phone}",
            self::RESEND_COOLDOWN_SECONDS
        );
    }

    /** Seconds remaining before a resend is allowed. */
    public function resendAvailableIn(string $phone, string $type): int
    {
        return RateLimiter::availableIn("otp_resend:{$type}:{$phone}");
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Deliver the OTP through the shared SMS sender. The user is actively waiting on
     * this code, so a provider failure must surface rather than be swallowed.
     */
    private function dispatch(string $phone, string $code): void
    {
        $appName = setting('site_name', config('app.name'));
        $message = "Your {$appName} verification code is: {$code}. Valid for ".self::TTL_MINUTES.' minutes.';

        $this->sms->sendOrFail($phone, $message);
    }
}
