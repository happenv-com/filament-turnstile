<?php

namespace Happenv\FilamentTurnstile\Concerns;

use Happenv\FilamentTurnstile\Forms\Components\Turnstile;
use Illuminate\Validation\ValidationException;

trait InteractsWithTurnstile
{
    protected function getTurnstileFormComponent(string $action = 'login'): Turnstile
    {
        return Turnstile::make('cf-turnstile-response')
            ->turnstileAction($action);
    }

    public function dispatchTurnstileReset(): void
    {
        $this->dispatch(
            (string) config('filament-turnstile.reset_event', 'turnstile-reset'),
        );
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->dispatchTurnstileReset();

        if (is_callable([parent::class, 'onValidationError'])) {
            parent::onValidationError($exception);
        }
    }
}
