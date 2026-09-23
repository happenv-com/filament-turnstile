<?php

use Happenv\FilamentTurnstile\Http\TurnstileVerifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns success when Cloudflare accepts the token', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => true,
            'error-codes' => [],
        ]),
    ]);

    $result = app(TurnstileVerifier::class)->verify('valid-token', '127.0.0.1');

    expect($result['success'])->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        && $request['response'] === 'valid-token'
        && $request['remoteip'] === '127.0.0.1'
        && filled($request['secret']));
});

it('returns failure for a blank token', function (): void {
    $result = app(TurnstileVerifier::class)->verify(null);

    expect($result['success'])->toBeFalse()
        ->and($result['error-codes'])->toContain('missing-input-response');
});

it('skips verification when keys are missing', function (): void {
    config([
        'filament-turnstile.site_key' => null,
        'filament-turnstile.secret_key' => null,
    ]);

    Http::fake();

    $verifier = app(TurnstileVerifier::class);

    expect($verifier->isConfigured())->toBeFalse()
        ->and($verifier->verify('anything')['success'])->toBeTrue();

    Http::assertNothingSent();
});

it('maps Cloudflare error codes', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ]),
    ]);

    $result = app(TurnstileVerifier::class)->verify('used-token');

    expect($result['success'])->toBeFalse()
        ->and($result['error-codes'])->toBe(['timeout-or-duplicate']);
});

it('answers Cloudflare test secret keys without a request', function (string $secretKey, bool $success, array $errorCodes): void {
    config(['filament-turnstile.secret_key' => $secretKey]);

    Http::preventStrayRequests();

    $result = app(TurnstileVerifier::class)->verify('XXXX.DUMMY.TOKEN.XXXX');

    expect($result['success'])->toBe($success)
        ->and($result['error-codes'])->toBe($errorCodes);
})->with([
    'always passes' => ['1x0000000000000000000000000000000AA', true, []],
    'always fails' => ['2x0000000000000000000000000000000AA', false, ['invalid-input-response']],
    'token already spent' => ['3x0000000000000000000000000000000AA', false, ['timeout-or-duplicate']],
]);
