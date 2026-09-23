<?php

namespace Happenv\FilamentTurnstile\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Encryption\DecryptException;
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
                $this->getTurnstileFormComponent('login')
                    ->hidden(fn (): bool => $this->hasPassedTurnstileForSubmittedUser()),
            ]);
    }

    protected function throwFailureValidationException(): never
    {
        $this->dispatchTurnstileReset();

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /**
     * Filament validates the login form again when the multi-factor challenge is
     * submitted, but Cloudflare accepts a token only once. A pending challenge
     * proves Turnstile already passed — provided it belongs to the user in the
     * form, so a challenge started for one account cannot skip Turnstile for
     * another.
     */
    protected function hasPassedTurnstileForSubmittedUser(): bool
    {
        if (blank($this->userUndertakingMultiFactorAuthentication)) {
            return false;
        }

        try {
            $challengedUserId = decrypt($this->userUndertakingMultiFactorAuthentication);
        } catch (DecryptException) {
            return false;
        }

        $authProvider = Filament::auth()->getProvider(); /** @phpstan-ignore-line */
        $submittedUser = $authProvider->retrieveByCredentials(
            $this->getCredentialsFromFormData($this->data ?? []),
        );

        return $submittedUser?->getAuthIdentifier() === $challengedUserId;
    }
}
