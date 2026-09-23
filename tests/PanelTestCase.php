<?php

namespace Happenv\FilamentTurnstile\Tests;

use Filament\Facades\Filament;
use Happenv\FilamentTurnstile\Tests\Fixtures\AdminPanelProvider;
use Happenv\FilamentTurnstile\Tests\Fixtures\User;
use Illuminate\Support\Facades\Hash;

/**
 * Boots a Filament panel with login, registration, password reset and a
 * multi-factor provider, all protected by Turnstile.
 */
abstract class PanelTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    protected function createUser(string $email = 'jane@example.com'): User
    {
        return User::create([
            'name' => 'Jane',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
