<?php

namespace Happenv\FilamentTurnstile\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstile;

class Login extends BaseLogin
{
    use InteractsWithTurnstile;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getTurnstileFormComponent('login'),
            ]);
    }

    protected function throwFailureValidationException(): never
    {
        $this->dispatchTurnstileReset();

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
