<?php

namespace Happenv\FilamentTurnstile\Tests;

use Illuminate\Support\Facades\Http;
use Happenv\FilamentTurnstile\Http\TurnstileVerifier;

class TurnstileVerifierTest extends TestCase
{
    public function test_it_returns_success_when_cloudflare_accepts_the_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'error-codes' => [],
            ]),
        ]);

        $result = app(TurnstileVerifier::class)->verify('valid-token', '127.0.0.1');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['response'] === 'valid-token'
                && $request['remoteip'] === '127.0.0.1'
                && filled($request['secret']);
        });
    }

    public function test_it_returns_failure_for_blank_token(): void
    {
        $result = app(TurnstileVerifier::class)->verify(null);

        $this->assertFalse($result['success']);
        $this->assertContains('missing-input-response', $result['error-codes']);
    }

    public function test_it_skips_verification_when_keys_are_missing(): void
    {
        config([
            'filament-turnstile.site_key' => null,
            'filament-turnstile.secret_key' => null,
        ]);

        Http::fake();

        $verifier = app(TurnstileVerifier::class);

        $this->assertFalse($verifier->isConfigured());
        $this->assertTrue($verifier->verify('anything')['success']);

        Http::assertNothingSent();
    }

    public function test_it_maps_cloudflare_error_codes(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['timeout-or-duplicate'],
            ]),
        ]);

        $result = app(TurnstileVerifier::class)->verify('used-token');

        $this->assertFalse($result['success']);
        $this->assertSame(['timeout-or-duplicate'], $result['error-codes']);
    }
}
