<?php

namespace Happenv\FilamentTurnstile\Tests;

use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Tests\Fixtures\CodeMultiFactorProvider;
use Happenv\FilamentTurnstile\Tests\Fixtures\CustomLogin;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

class LoginMultiFactorTest extends PanelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function loginPages(): array
    {
        return [
            'packaged page' => [Login::class],
            'custom page using the trait' => [CustomLogin::class],
        ];
    }

    #[DataProvider('loginPages')]
    public function test_user_passes_the_multi_factor_challenge_after_turnstile(string $page): void
    {
        $user = $this->createUser();

        Livewire::test($page)
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

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('loginPages')]
    public function test_login_requires_turnstile_before_the_multi_factor_challenge(string $page): void
    {
        $this->createUser();

        Livewire::test($page)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.cf-turnstile-response'])
            ->assertSet('userUndertakingMultiFactorAuthentication', null);
    }

    #[DataProvider('loginPages')]
    public function test_challenge_for_one_user_does_not_skip_turnstile_for_another(string $page): void
    {
        $this->createUser();

        $this->createUser('john@example.com');

        Livewire::test($page)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->set('data.cf-turnstile-response', 'token-1')
            ->call('authenticate')
            ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
            ->set('data.email', 'john@example.com')
            ->set('data.cf-turnstile-response', null)
            ->call('authenticate')
            ->assertHasErrors(['data.cf-turnstile-response']);

        $this->assertGuest();
    }
}
