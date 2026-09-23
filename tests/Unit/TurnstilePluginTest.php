<?php

namespace Happenv\FilamentTurnstile\Tests;

use Filament\Panel;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Pages\Auth\PasswordReset\RequestPasswordReset;
use Happenv\FilamentTurnstile\Pages\Auth\Register;
use Happenv\FilamentTurnstile\TurnstilePlugin;
use PHPUnit\Framework\Attributes\DataProvider;

class TurnstilePluginTest extends TestCase
{
    public function test_defaults_protect_login_only(): void
    {
        $plugin = TurnstilePlugin::make();

        $this->assertSame('filament-turnstile', $plugin->getId());
        $this->assertTrue($plugin->shouldProtectLogin());
        $this->assertFalse($plugin->shouldProtectRegistration());
        $this->assertFalse($plugin->shouldProtectPasswordReset());
        $this->assertTrue($plugin->isEnabled());
    }

    public function test_fluent_configuration(): void
    {
        $plugin = TurnstilePlugin::make()
            ->protectLogin(false)
            ->protectRegistration()
            ->protectPasswordReset()
            ->theme('dark')
            ->size('flexible')
            ->language('en')
            ->enabled(fn (): bool => true);

        $this->assertFalse($plugin->shouldProtectLogin());
        $this->assertTrue($plugin->shouldProtectRegistration());
        $this->assertTrue($plugin->shouldProtectPasswordReset());
        $this->assertSame('dark', $plugin->getTheme());
        $this->assertSame('flexible', $plugin->getSize());
        $this->assertSame('en', $plugin->getLanguage());
        $this->assertTrue($plugin->isEnabled());
    }

    public function test_register_swaps_auth_pages(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset();

        TurnstilePlugin::make()
            ->protectRegistration()
            ->protectPasswordReset()
            ->register($panel);

        $this->assertSame(Login::class, $panel->getLoginRouteAction());
        $this->assertSame(Register::class, $panel->getRegistrationRouteAction());
        $this->assertSame(RequestPasswordReset::class, $panel->getRequestPasswordResetRouteAction());
    }

    /**
     * @return array<string, array{bool, bool, bool}>
     */
    public static function singleProtectedPage(): array
    {
        return [
            'login only' => [true, false, false],
            'registration only' => [false, true, false],
            'password reset only' => [false, false, true],
        ];
    }

    #[DataProvider('singleProtectedPage')]
    public function test_each_auth_page_is_protected_independently(bool $login, bool $registration, bool $passwordReset): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset();

        TurnstilePlugin::make()
            ->protectLogin($login)
            ->protectRegistration($registration)
            ->protectPasswordReset($passwordReset)
            ->register($panel);

        $this->assertSame($login, $panel->getLoginRouteAction() === Login::class);
        $this->assertSame($registration, $panel->getRegistrationRouteAction() === Register::class);
        $this->assertSame($passwordReset, $panel->getRequestPasswordResetRouteAction() === RequestPasswordReset::class);
    }

    public function test_register_skips_when_protection_disabled(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->path('admin')
            ->login();

        TurnstilePlugin::make()
            ->protectLogin(false)
            ->register($panel);

        $this->assertNotSame(Login::class, $panel->getLoginRouteAction());
    }
}
