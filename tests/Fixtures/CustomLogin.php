<?php

declare(strict_types=1);

namespace Happenv\FilamentTurnstile\Tests\Fixtures;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstileLogin;

class CustomLogin extends BaseLogin
{
    use InteractsWithTurnstileLogin;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getTurnstileFormComponent(),
            ]);
    }
}
