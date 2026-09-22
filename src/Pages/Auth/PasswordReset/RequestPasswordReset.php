<?php

namespace Happenv\FilamentTurnstile\Pages\Auth\PasswordReset;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstile;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    use InteractsWithTurnstile;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getTurnstileFormComponent('password-reset'),
            ]);
    }

    public function request(): void
    {
        parent::request();

        $this->dispatchTurnstileReset();
    }
}
