<?php

namespace Happenv\FilamentTurnstile\Concerns;

use Filament\Schemas\Schema;

/**
 * Turnstile for a page extending Filament's `Register`. Overriding `form()` is
 * fine — keep `$this->getTurnstileFormComponent('register')` in the schema.
 */
trait InteractsWithTurnstileRegistration
{
    use InteractsWithTurnstile;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getTurnstileFormComponent('register'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        unset($data['cf-turnstile-response']);

        return parent::mutateFormDataBeforeRegister($data);
    }
}
