<?php

namespace Happenv\FilamentTurnstile\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    /**
     * @return array{success: bool, error-codes?: array<int, string>}
     */
    public function verify(?string $token, ?string $remoteIp = null): array
    {
        if (! $this->isConfigured()) {
            return ['success' => true, 'error-codes' => []];
        }

        if (blank($token)) {
            return [
                'success' => false,
                'error-codes' => ['missing-input-response'],
            ];
        }

        $payload = [
            'secret' => $this->secretKey(),
            'response' => $token,
        ];

        if (filled($remoteIp)) {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout((int) config('filament-turnstile.connect_timeout', 5))
                ->timeout((int) config('filament-turnstile.timeout', 10))
                ->post((string) config('filament-turnstile.verify_url'), $payload);
        } catch (ConnectionException) {
            return [
                'success' => false,
                'error-codes' => ['internal-error'],
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'error-codes' => ['internal-error'],
            ];
        }

        /** @var array{success?: bool, error-codes?: array<int, string>} $body */
        $body = $response->json() ?? [];

        return [
            'success' => (bool) ($body['success'] ?? false),
            'error-codes' => $body['error-codes'] ?? [],
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->siteKey()) && filled($this->secretKey());
    }

    public function siteKey(): ?string
    {
        $key = config('filament-turnstile.site_key');

        return filled($key) ? (string) $key : null;
    }

    public function secretKey(): ?string
    {
        $key = config('filament-turnstile.secret_key');

        return filled($key) ? (string) $key : null;
    }
}
