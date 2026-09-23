<?php

use Filament\Panel;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Pages\Auth\PasswordReset\RequestPasswordReset;
use Happenv\FilamentTurnstile\Pages\Auth\Register;
use Happenv\FilamentTurnstile\TurnstilePlugin;

function authPanel(): Panel
{
    return Panel::make()
        ->id('admin')
        ->path('admin')
        ->login()
        ->registration()
        ->passwordReset();
}

it('protects only login by default', function (): void {
    expect(TurnstilePlugin::make())
        ->getId()->toBe('filament-turnstile')
        ->shouldProtectLogin()->toBeTrue()
        ->shouldProtectRegistration()->toBeFalse()
        ->shouldProtectPasswordReset()->toBeFalse()
        ->isEnabled()->toBeTrue();
});

it('is configured fluently', function (): void {
    $plugin = TurnstilePlugin::make()
        ->protectLogin(false)
        ->protectRegistration()
        ->protectPasswordReset()
        ->theme('dark')
        ->size('flexible')
        ->language('en')
        ->enabled(fn (): bool => true);

    expect($plugin)
        ->shouldProtectLogin()->toBeFalse()
        ->shouldProtectRegistration()->toBeTrue()
        ->shouldProtectPasswordReset()->toBeTrue()
        ->getTheme()->toBe('dark')
        ->getSize()->toBe('flexible')
        ->getLanguage()->toBe('en')
        ->isEnabled()->toBeTrue();
});

it('swaps the auth pages on register', function (): void {
    $panel = authPanel();

    TurnstilePlugin::make()
        ->protectRegistration()
        ->protectPasswordReset()
        ->register($panel);

    expect($panel)
        ->getLoginRouteAction()->toBe(Login::class)
        ->getRegistrationRouteAction()->toBe(Register::class)
        ->getRequestPasswordResetRouteAction()->toBe(RequestPasswordReset::class);
});

it('protects each auth page independently', function (bool $login, bool $registration, bool $passwordReset): void {
    $panel = authPanel();

    TurnstilePlugin::make()
        ->protectLogin($login)
        ->protectRegistration($registration)
        ->protectPasswordReset($passwordReset)
        ->register($panel);

    expect($panel->getLoginRouteAction() === Login::class)->toBe($login)
        ->and($panel->getRegistrationRouteAction() === Register::class)->toBe($registration)
        ->and($panel->getRequestPasswordResetRouteAction() === RequestPasswordReset::class)->toBe($passwordReset);
})->with([
    'login only' => [true, false, false],
    'registration only' => [false, true, false],
    'password reset only' => [false, false, true],
]);

it('skips the swap when protection is disabled', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->login();

    TurnstilePlugin::make()
        ->protectLogin(false)
        ->register($panel);

    expect($panel->getLoginRouteAction())->not->toBe(Login::class);
});
