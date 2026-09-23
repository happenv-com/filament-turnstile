<?php

namespace Happenv\FilamentTurnstile\Tests;

use Filament\Auth\Pages\Login as FilamentLogin;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Pages\Auth\PasswordReset\RequestPasswordReset;
use Happenv\FilamentTurnstile\Pages\Auth\Register;
use Happenv\FilamentTurnstile\Tests\Fixtures\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;

class TestsTurnstileTest extends PanelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The helpers use Cloudflare's test keys, which the verifier answers locally.
        Http::preventStrayRequests();
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function protectedPages(): array
    {
        return [
            'login' => [Login::class, 'authenticate'],
            'registration' => [Register::class, 'register'],
            'password reset request' => [RequestPasswordReset::class, 'request'],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_turnstile_blocks_each_protected_page(string $page, string $method): void
    {
        Livewire::test($page)
            ->assertTurnstileActive()
            ->assertTurnstileBlocks($method);
    }

    #[DataProvider('protectedPages')]
    public function test_turnstile_allows_each_protected_page(string $page, string $method): void
    {
        Livewire::test($page)->assertTurnstileAllows($method);
    }

    public function test_allowed_login_reaches_the_multi_factor_challenge(): void
    {
        $this->createUser();

        Livewire::test(Login::class)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->assertTurnstileAllows('authenticate')
            ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
            ->assertTurnstileInactive();
    }

    public function test_blocked_login_does_not_check_the_password(): void
    {
        $this->createUser();

        Livewire::test(Login::class)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->assertTurnstileBlocks('authenticate')
            ->assertSet('userUndertakingMultiFactorAuthentication', null);
    }

    public function test_pass_turnstile_lets_the_registration_through(): void
    {
        Livewire::test(Register::class)
            ->set('data.name', 'Jane')
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->set('data.passwordConfirmation', 'password')
            ->passTurnstile()
            ->call('register')
            ->assertHasNoErrors();

        $this->assertTrue(User::where('email', 'jane@example.com')->exists());
    }

    public function test_fail_turnstile_stops_the_login(): void
    {
        $this->createUser();

        Livewire::test(Login::class)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->failTurnstile()
            ->call('authenticate')
            ->assertHasErrors(['data.cf-turnstile-response'])
            ->assertSet('userUndertakingMultiFactorAuthentication', null);
    }

    public function test_turnstile_is_inactive_without_keys(): void
    {
        config([
            'filament-turnstile.site_key' => null,
            'filament-turnstile.secret_key' => null,
        ]);

        Livewire::test(Login::class)->assertTurnstileInactive();
    }

    public function test_turnstile_is_inactive_on_a_page_without_it(): void
    {
        Livewire::test(FilamentLogin::class)->assertTurnstileInactive();
    }

    public function test_blocks_assertion_fails_on_a_page_without_turnstile(): void
    {
        $this->expectException(AssertionFailedError::class);

        Livewire::test(FilamentLogin::class)->assertTurnstileBlocks('authenticate');
    }

    public function test_active_assertion_fails_when_turnstile_is_inactive(): void
    {
        config(['filament-turnstile.secret_key' => null]);

        $this->expectException(AssertionFailedError::class);

        Livewire::test(Login::class)->assertTurnstileActive();
    }
}
