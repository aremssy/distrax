<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Single SMS egress point for the whole application (OTP codes and notification
 * SMS alike). Drivers are admin-configurable via the `sms_driver` setting; the
 * legacy `otp_driver` setting is still honoured so existing installs keep working.
 */
class SmsSender
{
    /**
     * Zan Communications documented failure codes. The API returns a bare numeric
     * code in the response body; anything not in this list is treated as accepted
     * (a successful submission returns a different, non-error code).
     *
     * @var array<string, string>
     */
    private const ZAN_ERROR_CODES = [
        '1002' => 'Sender Id/Masking Not Found',
        '1003' => 'API Not Found',
        '1004' => 'SPAM Detected',
        '1005' => 'Internal Error',
        '1006' => 'Internal Error',
        '1007' => 'Balance Insufficient',
        '1008' => 'Message is empty',
        '1009' => 'Message Type Not Set (text/unicode)',
        '1010' => 'Invalid User & Password',
        '1011' => 'Invalid User Id',
        '1012' => 'Invalid Number',
        '1013' => 'API limit error',
        '1014' => 'No matching template',
        '1015' => 'SMS Content Validation Fails',
    ];

    /**
     * Send an SMS. Returns true when the provider accepted the message.
     *
     * Failures are logged and reported via the return value rather than thrown, so a
     * best-effort caller (e.g. a notification fan-out) is never broken by a provider
     * outage. Callers that must not proceed on failure — OTP delivery — should check
     * the return value and react.
     */
    public function send(string $phone, string $message): bool
    {
        return match ($this->driver()) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            'vonage' => $this->sendViaVonage($phone, $message),
            'zan' => $this->sendViaZan($phone, $message),
            default => $this->sendViaLog($phone, $message),
        };
    }

    /**
     * True when a real provider (not the log driver) is configured, so callers can
     * tell "delivered to a carrier" apart from "written to the log for local dev".
     */
    public function isLiveDriver(): bool
    {
        return in_array($this->driver(), ['twilio', 'vonage', 'zan'], true);
    }

    public function driver(): string
    {
        return (string) (setting('sms_driver') ?: setting('otp_driver', 'log'));
    }

    private function sendViaLog(string $phone, string $message): bool
    {
        Log::info("[SMS] To: {$phone} | {$message}");

        return true;
    }

    private function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = setting('twilio_sid');
        $token = setting('twilio_token');
        $from = setting('twilio_from');

        if (! $sid || ! $token || ! $from) {
            Log::error('[SMS] Twilio is selected but twilio_sid / twilio_token / twilio_from are not fully configured.');

            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $phone,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            Log::error('[SMS] Twilio send failed', ['status' => $response->status(), 'body' => $response->json(), 'phone' => $phone]);

            return false;
        }

        return true;
    }

    private function sendViaVonage(string $phone, string $message): bool
    {
        $key = setting('vonage_api_key');
        $secret = setting('vonage_api_secret');

        if (! $key || ! $secret) {
            Log::error('[SMS] Vonage is selected but vonage_api_key / vonage_api_secret are not configured.');

            return false;
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $key,
            'api_secret' => $secret,
            'to' => $phone,
            'from' => setting('vonage_from', 'Ready'),
            'text' => $message,
        ]);

        // Vonage returns HTTP 200 even on failure — the per-message `status` is the
        // real result, where "0" means delivered to the carrier.
        $status = $response->json('messages.0.status');

        if ($response->failed() || $status !== '0') {
            Log::error('[SMS] Vonage send failed', [
                'status' => $status,
                'error' => $response->json('messages.0.error-text'),
                'phone' => $phone,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send through Zan Communications' HTTP API (Bangladesh gateway).
     *
     * The API returns a bare numeric string in the body: a documented error code
     * (1002–1015) on rejection, or a different success code on acceptance. We treat
     * any non-error code on a 2xx response as delivered. Bangla (non-ASCII) content
     * is sent as `unicode`; everything else as `text`.
     */
    private function sendViaZan(string $phone, string $message): bool
    {
        $apiKey = setting('zan_api_key');
        $senderId = setting('zan_sender_id');

        if (! $apiKey || ! $senderId) {
            Log::error('[SMS] Zan is selected but zan_api_key / zan_sender_id are not configured.');

            return false;
        }

        $response = Http::asForm()->post('https://sms.zancommunicationsbd.com/smsapi', [
            'api_key' => $apiKey,
            'type' => $this->isUnicode($message) ? 'unicode' : 'text',
            'contacts' => preg_replace('/\D/', '', $phone),
            'senderid' => $senderId,
            'msg' => $message,
            'label' => 'transactional',
        ]);

        $code = trim($response->body());

        if ($response->failed() || isset(self::ZAN_ERROR_CODES[$code])) {
            Log::error('[SMS] Zan send failed', [
                'code' => $code,
                'meaning' => self::ZAN_ERROR_CODES[$code] ?? 'Unknown error',
                'phone' => $phone,
            ]);

            return false;
        }

        return true;
    }

    /**
     * True when the message contains characters outside the 7-bit ASCII range (e.g.
     * Bangla), which Zan requires be sent with `type=unicode`.
     */
    private function isUnicode(string $message): bool
    {
        return (bool) preg_match('/[^\x00-\x7F]/', $message);
    }

    /**
     * Send or fail loudly — for delivery that the user is actively waiting on.
     *
     * @throws RuntimeException
     */
    public function sendOrFail(string $phone, string $message): void
    {
        if (! $this->send($phone, $message)) {
            throw new RuntimeException('Failed to send SMS.');
        }
    }
}
