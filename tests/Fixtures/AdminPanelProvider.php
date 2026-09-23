<?php

namespace Happenv\FilamentTurnstile\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Happenv\FilamentTurnstile\TurnstilePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->login()
            ->registration()
            ->passwordReset()
            ->multiFactorAuthentication([new CodeMultiFactorProvider])
            ->plugin(
                TurnstilePlugin::make()
                    ->protectRegistration()
                    ->protectPasswordReset(),
            );
    }
}
