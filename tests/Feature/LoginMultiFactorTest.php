<?php

use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Tests\Fixtures\CodeMultiFactorProvider;
use Happenv\FilamentTurnstile\Tests\Fixtures\CustomLogin;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    // Cloudflare accepts a token exactly once; a second siteverify of the same token fails.
    $verifiedTokens = [];

    Http::fake([
        'challenges.cloudflare.com/*' => function (Request $request) use (&$verifiedTokens) {
            $token = $request['response'];

            if (in_array($token, $verifiedTokens, true)) {
                return Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);
            }

            $verifiedTokens[] = $token;

            return Http::response(['success' => true, 'error-codes' => []]);
        },
    ]);
});

dataset('login pages', [
    'packaged page' => [Login::class],
    'custom page using the trait' => [CustomLogin::class],
]);

it('lets the user pass the multi-factor challenge after Turnstile', function (string $page): void {
    $user = $this->createUser();

    livewire($page)
        ->assertSeeHtml('fi-fo-turnstile')
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->set('data.cf-turnstile-response', 'token-1')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
        ->assertDontSeeHtml('fi-fo-turnstile')
        ->set('data.multiFactor.code.code', CodeMultiFactorProvider::CODE)
        ->call('authenticate')
        ->assertHasNoErrors();

    assertAuthenticatedAs($user);
})->with('login pages');

it('requires Turnstile before the multi-factor challenge', function (string $page): void {
    $this->createUser();

    livewire($page)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['data.cf-turnstile-response'])
        ->assertSet('userUndertakingMultiFactorAuthentication', null);
})->with('login pages');

it('does not let a challenge for one user skip Turnstile for another', function (string $page): void {
    $this->createUser();

    $this->createUser('john@example.com');

    livewire($page)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->set('data.cf-turnstile-response', 'token-1')
        ->call('authenticate')
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
        ->set('data.email', 'john@example.com')
        ->set('data.cf-turnstile-response')
        ->call('authenticate')
        ->assertHasErrors(['data.cf-turnstile-response']);

    assertGuest();
})->with('login pages');
