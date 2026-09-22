<?php

namespace Happenv\FilamentTurnstile\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstile;

class Register extends BaseRegister
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
