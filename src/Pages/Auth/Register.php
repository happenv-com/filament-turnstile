<?php

declare(strict_types=1);

namespace Happenv\FilamentTurnstile\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstileRegistration;

class Register extends BaseRegister
{
    use InteractsWithTurnstileRegistration;
}
