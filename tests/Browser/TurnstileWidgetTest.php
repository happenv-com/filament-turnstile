<?php

use Happenv\FilamentTurnstile\Testing\TestsTurnstile;
use Happenv\FilamentTurnstile\Tests\Fixtures\CodeMultiFactorProvider;
use Happenv\FilamentTurnstile\Tests\Fixtures\User;

/*
 * These tests load the real widget from challenges.cloudflare.com with
 * Cloudflare's test site key, which solves itself and hands the page the
 * dummy token. Run them with `composer test:browser`.
 */

beforeEach(function (): void {
    $this->artisan('filament:assets');

    config([
        'filament-turnstile.site_key' => TestsTurnstile::PASSING_SITE_KEY,
        'filament-turnstile.secret_key' => TestsTurnstile::PASSING_SECRET_KEY,
    ]);
});

// The widget writes its token to a hidden input once it is solved.
const TURNSTILE_TOKEN_SCRIPT = "document.querySelector('.fi-fo-turnstile [name=\"cf-turnstile-response\"]')?.value";

it('logs the user in through Turnstile and the multi-factor challenge', function (): void {
    $this->createUser();

    visit('/admin/login')
        ->assertScript(TURNSTILE_TOKEN_SCRIPT, TestsTurnstile::DUMMY_TOKEN)
        ->fill('[id="form.email"]', 'jane@example.com')
        ->fill('[id="form.password"]', 'password')
        ->submit()
        ->waitForText('Code')
        ->assertNotPresent('.fi-fo-turnstile')
        ->fill('[id="multiFactorChallengeForm.code.code"]', CodeMultiFactorProvider::CODE)
        ->submit()
        ->waitForText('Dashboard')
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
});

it('shows the error and keeps the user out when the server rejects the token', function (): void {
    // The widget still solves with the passing site key; siteverify rejects its token.
    config(['filament-turnstile.secret_key' => TestsTurnstile::BLOCKING_SECRET_KEY]);

    $this->createUser();

    visit('/admin/login')
        ->assertScript(TURNSTILE_TOKEN_SCRIPT, TestsTurnstile::DUMMY_TOKEN)
        ->fill('[id="form.email"]', 'jane@example.com')
        ->fill('[id="form.password"]', 'password')
        ->submit()
        ->waitForText(__('filament-turnstile::validation.invalid-input-response'))
        ->assertPathIs('/admin/login')
        ->assertPresent('.fi-fo-turnstile')
        ->assertNoJavaScriptErrors();
});

it('registers the user through Turnstile', function (): void {
    visit('/admin/register')
        ->assertScript(TURNSTILE_TOKEN_SCRIPT, TestsTurnstile::DUMMY_TOKEN)
        ->fill('[id="form.name"]', 'Jane')
        ->fill('[id="form.email"]', 'jane@example.com')
        ->fill('[id="form.password"]', 'password')
        ->fill('[id="form.passwordConfirmation"]', 'password')
        ->submit()
        ->waitForText('Dashboard')
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});
