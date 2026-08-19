<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SocialAuthService
{
    /**
     * Verify a social provider token and return a normalised user payload.
     *
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null}
     *
     * @throws Exception When the token is invalid or the provider is unsupported.
     */
    public function verify(string $provider, string $token): array
    {
        return match (strtolower($provider)) {
            'google' => $this->verifyGoogle($token),
            'facebook' => $this->verifyFacebook($token),
            default => throw new Exception("Unsupported social provider: {$provider}"),
        };
    }

    /**
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null}
     */
    private function verifyGoogle(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            throw new Exception('Invalid Google token.');
        }

        $data = $response->json();

        $clientId = setting('google_client_id');
        if ($clientId && ($data['aud'] ?? '') !== $clientId) {
            throw new Exception('Google token audience mismatch.');
        }

        return [
            'id' => $data['sub'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'avatar' => $data['picture'] ?? null,
        ];
    }

    /**
     * @return array{id: string, email: string|null, name: string|null, avatar: string|null}
     */
    private function verifyFacebook(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,email,picture.type(large)',
        ]);

        if ($response->failed()) {
            throw new Exception('Invalid Facebook token.');
        }

        $data = $response->json();

        if (empty($data['id'])) {
            throw new Exception('Could not retrieve Facebook user ID.');
        }

        return [
            'id' => $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'avatar' => $data['picture']['data']['url'] ?? null,
        ];
    }
}
