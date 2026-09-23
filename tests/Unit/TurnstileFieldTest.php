<?php

use Happenv\FilamentTurnstile\Forms\Components\Turnstile;
use Happenv\FilamentTurnstile\Rules\TurnstileRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

it('passes the rule when verification succeeds', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => true,
            'error-codes' => [],
        ]),
    ]);

    $validator = Validator::make(
        ['cf-turnstile-response' => 'token'],
        ['cf-turnstile-response' => [new TurnstileRule]],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails the rule when verification rejects the token', function (): void {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    $validator = Validator::make(
        ['cf-turnstile-response' => 'bad-token'],
        ['cf-turnstile-response' => [new TurnstileRule]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->get('cf-turnstile-response'))->not->toBeEmpty();
});

it('exposes the widget options, reset event and site key', function (): void {
    $field = Turnstile::make('cf-turnstile-response')
        ->theme('dark')
        ->size('compact')
        ->language('en-US')
        ->turnstileAction('login')
        ->resetEvent('custom-reset');

    expect($field)
        ->getResetEvent()->toBe('custom-reset')
        ->getTheme()->toBe('dark')
        ->getSize()->toBe('compact')
        ->getLanguage()->toBe('en-US')
        ->getTurnstileAction()->toBe('login')
        ->getSiteKey()->toBe('test-site-key')
        ->shouldRenderWidget()->toBeTrue();

    expect(file_get_contents(__DIR__ . '/../../resources/views/components/turnstile.blade.php'))
        ->toContain('x-on:{{ $resetEvent }}.window')
        ->toContain('turnstile.render');
});

it('hides the field when keys are missing', function (): void {
    config([
        'filament-turnstile.site_key' => null,
        'filament-turnstile.secret_key' => null,
    ]);

    expect(Turnstile::make('cf-turnstile-response')->shouldRenderWidget())->toBeFalse();
});
