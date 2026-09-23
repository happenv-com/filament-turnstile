<?php

namespace Happenv\FilamentTurnstile\Concerns;

use Filament\Schemas\Schema;

/**
 * Turnstile for a page extending Filament's `RequestPasswordReset`. Overriding
 * `form()` is fine — keep `$this->getTurnstileFormComponent('password-reset')`
 * in the schema.
 */
trait InteractsWithTurnstilePasswordReset
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
