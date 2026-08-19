<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging over the HTTP v1 API.
 *
 * The legacy `fcm.googleapis.com/fcm/send` endpoint (server-key auth, multicast
 * `registration_ids`) was decommissioned by Google in 2024. v1 requires an OAuth2
 * bearer token minted from a service account and accepts exactly one token per
 * request, so we sign our own JWT (RS256 via ext-openssl — no extra dependency)
 * and fan out per device.
 */
class FcmSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const ACCESS_TOKEN_CACHE_KEY = 'fcm:access_token';

    /**
     * Push to every registered device of a user. Best-effort: failures are logged,
     * never thrown. Device tokens the provider reports as dead are pruned.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $credentials = $this->credentials();

        if (! $credentials) {
            return;
        }

        $tokens = Device::where('user_id', $userId)->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $accessToken = $this->accessToken($credentials);

        if (! $accessToken) {
            return;
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send";

        foreach ($tokens as $token) {
            $this->sendOne($endpoint, $accessToken, $token, $title, $body, $data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendOne(string $endpoint, string $accessToken, string $token, string $title, string $body, array $data): void
    {
        $response = Http::withToken($accessToken)->post($endpoint, [
            'message' => [
                'token' => $token,
                'notification' => ['title' => $title, 'body' => $body],
                // FCM v1 requires all data values to be strings.
                'data' => array_map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $data),
            ],
        ]);

        if ($response->successful()) {
            return;
        }

        $status = $response->json('error.details.0.errorCode') ?? $response->json('error.status');

        // The device is gone or the token is malformed — stop pushing to it.
        if (in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)) {
            Device::where('token', $token)->delete();

            return;
        }

        Log::warning('[FCM] Push failed', ['status' => $response->status(), 'error' => $response->json('error.message')]);
    }

    /**
     * Service-account credentials, from the JSON blob pasted into admin settings.
     *
     * @return array{project_id: string, client_email: string, private_key: string}|null
     */
    private function credentials(): ?array
    {
        $raw = setting('firebase_service_account');

        if (! $raw) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded) || ! isset($decoded['project_id'], $decoded['client_email'], $decoded['private_key'])) {
            Log::error('[FCM] firebase_service_account is not a valid service-account JSON key.');

            return null;
        }

        return [
            'project_id' => $decoded['project_id'],
            'client_email' => $decoded['client_email'],
            'private_key' => $decoded['private_key'],
        ];
    }

    /**
     * Exchange a self-signed JWT for a short-lived OAuth2 access token.
     * Cached just under Google's one-hour lifetime.
     *
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    private function accessToken(array $credentials): ?string
    {
        return Cache::remember(self::ACCESS_TOKEN_CACHE_KEY, now()->addMinutes(55), function () use ($credentials): ?string {
            $jwt = $this->signedJwt($credentials);

            if (! $jwt) {
                return null;
            }

            $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                Log::error('[FCM] Could not mint an access token', ['status' => $response->status(), 'body' => $response->json()]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    private function signedJwt(array $credentials): ?string
    {
        $issuedAt = now()->timestamp;

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ];

        $payload = $this->base64Url(json_encode($header)).'.'.$this->base64Url(json_encode($claims));

        $signature = '';

        if (! openssl_sign($payload, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::error('[FCM] Could not sign the service-account JWT — check that private_key is a valid PEM key.');

            return null;
        }

        return $payload.'.'.$this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
