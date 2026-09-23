<?php

use Filament\Auth\Pages\Login as FilamentLogin;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Pages\Auth\PasswordReset\RequestPasswordReset;
use Happenv\FilamentTurnstile\Pages\Auth\Register;
use Happenv\FilamentTurnstile\Tests\Fixtures\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\AssertionFailedError;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    // The helpers use Cloudflare's test keys, which the verifier answers locally.
    Http::preventStrayRequests();
});

dataset('protected pages', [
    'login' => [Login::class, 'authenticate'],
    'registration' => [Register::class, 'register'],
    'password reset request' => [RequestPasswordReset::class, 'request'],
]);

it('blocks each protected page', function (string $page, string $method): void {
    livewire($page)
        ->assertTurnstileActive()
        ->assertTurnstileBlocks($method);
})->with('protected pages');

it('allows each protected page', function (string $page, string $method): void {
    livewire($page)->assertTurnstileAllows($method);
})->with('protected pages');

it('lets an allowed login reach the multi-factor challenge', function (): void {
    $this->createUser();

    livewire(Login::class)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->assertTurnstileAllows('authenticate')
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
        ->assertTurnstileInactive();
});

it('does not check the password of a blocked login', function (): void {
    $this->createUser();

    livewire(Login::class)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->assertTurnstileBlocks('authenticate')
        ->assertSet('userUndertakingMultiFactorAuthentication', null);
});

it('lets the registration through with passTurnstile()', function (): void {
    livewire(Register::class)
        ->set('data.name', 'Jane')
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->passTurnstile()
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('stops the login with failTurnstile()', function (): void {
    $this->createUser();

    livewire(Login::class)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->failTurnstile()
        ->call('authenticate')
        ->assertHasErrors(['data.cf-turnstile-response'])
        ->assertSet('userUndertakingMultiFactorAuthentication', null);
});

it('is inactive without keys', function (): void {
    config([
        'filament-turnstile.site_key' => null,
        'filament-turnstile.secret_key' => null,
    ]);

    livewire(Login::class)->assertTurnstileInactive();
});

it('is inactive on a page without it', function (): void {
    livewire(FilamentLogin::class)->assertTurnstileInactive();
});

it('fails the blocks assertion on a page without Turnstile', function (): void {
    livewire(FilamentLogin::class)->assertTurnstileBlocks('authenticate');
})->throws(AssertionFailedError::class);

it('fails the active assertion when Turnstile is inactive', function (): void {
    config(['filament-turnstile.secret_key' => null]);

    livewire(Login::class)->assertTurnstileActive();
})->throws(AssertionFailedError::class);
