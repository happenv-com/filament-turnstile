<?php

namespace Happenv\FilamentTurnstile\Tests;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelProvider;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Tests\Fixtures\CodeMultiFactorProvider;
use Happenv\FilamentTurnstile\Tests\Fixtures\User;
use Happenv\FilamentTurnstile\TurnstilePlugin;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class LoginMultiFactorTest extends TestCase
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
            LoginMultiFactorPanelProvider::class,
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

        // Cloudflare accepts a token exactly once; a second siteverify of the same token fails.
        $verifiedTokens = [];

        Http::fake([
            'challenges.cloudflare.com/*' => function (Request $request) use (&$verifiedTokens) {
                $token = $request['response'];

                if (in_array($token, $verifiedTokens, true)) {
                    return Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);
                }

                $verifiedTokens[] = $token;

                return Http::response(['success' => true, 'error-codes' => []]);
            },
        ]);
    }

    public function test_user_passes_the_multi_factor_challenge_after_turnstile(): void
    {
        $user = User::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(Login::class)
            ->assertSeeHtml('fi-fo-turnstile')
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->set('data.cf-turnstile-response', 'token-1')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
            ->assertDontSeeHtml('fi-fo-turnstile')
            ->set('data.multiFactor.code.code', CodeMultiFactorProvider::CODE)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_requires_turnstile_before_the_multi_factor_challenge(): void
    {
        User::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.cf-turnstile-response'])
            ->assertSet('userUndertakingMultiFactorAuthentication', null);
    }

    public function test_challenge_for_one_user_does_not_skip_turnstile_for_another(): void
    {
        User::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'jane@example.com')
            ->set('data.password', 'password')
            ->set('data.cf-turnstile-response', 'token-1')
            ->call('authenticate')
            ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
            ->set('data.email', 'john@example.com')
            ->set('data.cf-turnstile-response', null)
            ->call('authenticate')
            ->assertHasErrors(['data.cf-turnstile-response']);

        $this->assertGuest();
    }
}

class LoginMultiFactorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->login()
            ->multiFactorAuthentication([new CodeMultiFactorProvider])
            ->plugin(TurnstilePlugin::make());
    }
}
