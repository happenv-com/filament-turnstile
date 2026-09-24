<?php

declare(strict_types=1);

namespace Happenv\FilamentTurnstile\Pages\Auth\PasswordReset;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstilePasswordReset;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    use InteractsWithTurnstilePasswordReset;
}
