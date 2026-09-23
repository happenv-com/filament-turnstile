<?php

namespace Happenv\FilamentTurnstile\Concerns;

use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Forms\Components\Turnstile;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Validation\ValidationException;

/**
 * Turnstile for a page extending Filament's `Login`. Overriding `form()` is
 * fine — keep `$this->getTurnstileFormComponent()` in the schema.
 */
trait InteractsWithTurnstileLogin
{
    use InteractsWithTurnstile {
        getTurnstileFormComponent as getBaseTurnstileFormComponent;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getTurnstileFormComponent(),
            ]);
    }

    protected function getTurnstileFormComponent(string $action = 'login'): Turnstile
    {
        return $this->getBaseTurnstileFormComponent($action)
            ->hidden(fn (): bool => $this->hasPassedTurnstileForSubmittedUser());
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
