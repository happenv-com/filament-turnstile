<?php

declare(strict_types=1);

namespace Happenv\FilamentTurnstile\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstileLogin;

class Login extends BaseLogin
{
    use InteractsWithTurnstileLogin;
}
