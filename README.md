# Filament Turnstile

Cloudflare Turnstile panel plugin for **Filament v5**. Register it once on a panel and login is protected automatically — optionally registration and password-reset request too. No form schema edits for the common case.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^11` / `^12` |
| Filament | `^5.0` |

## Installation

```bash
composer require happenv/filament-turnstile
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=filament-turnstile-config
```

Add your keys to `.env`:

```env
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

Create a widget and get keys at [dash.cloudflare.com](https://dash.cloudflare.com) → **Turnstile**.

## Usage

### Panel plugin (recommended)

Call `->plugin(...)` **after** `->login()` / `->registration()` / `->passwordReset()` so the plugin can swap the auth page classes:

```php
use Happenv\FilamentTurnstile\TurnstilePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->login()
        ->registration()
        ->passwordReset()
        ->plugin(
            TurnstilePlugin::make()
                ->protectRegistration()   // opt-in
                ->protectPasswordReset()  // opt-in (request page only)
        );
}
```

**Defaults:** login is protected; registration and password-reset request are not.

Each page is toggled independently, so any combination works — e.g. registration and password reset only:

```php
TurnstilePlugin::make()
    ->protectLogin(false)
    ->protectRegistration()
    ->protectPasswordReset()
```

Login protection works with Filament multi-factor authentication (app codes, passkeys): Turnstile appears only on the password step, and the multi-factor challenge step has no widget and no second verification.

### Widget options

```php
TurnstilePlugin::make()
    ->theme('auto')      // auto | light | dark
    ->size('flexible')   // normal | flexible | compact
    ->language('en-US')
    ->enabled(true);
```

### Manual form field

For custom auth pages or any Filament form:

```php
use Happenv\FilamentTurnstile\Forms\Components\Turnstile;

Turnstile::make('cf-turnstile-response')
    ->theme('dark')
    ->size('compact')
    ->turnstileAction('contact');
```

Disable auto-swap and extend or compose yourself:

```php
->login(CustomLogin::class)
->plugin(TurnstilePlugin::make()->protectLogin(false))
```

### Custom auth pages

Keep extending Filament's page and add the matching trait — it carries everything the packaged page does:

| Filament page | Trait | What it does |
|---|---|---|
| `Filament\Auth\Pages\Login` | `InteractsWithTurnstileLogin` | Default form with the widget. Resets the widget after a wrong password. Skips the widget on the multi-factor challenge step (app codes, passkeys) — the token was already verified on the password step, and Cloudflare accepts a token only once. |
| `Filament\Auth\Pages\Register` | `InteractsWithTurnstileRegistration` | Default form with the widget. Keeps the token out of the data passed to user creation. |
| `Filament\Auth\Pages\PasswordReset\RequestPasswordReset` | `InteractsWithTurnstilePasswordReset` | Default form with the widget. Resets the widget after each request, so another one can be sent. |

All three reset the widget on any validation error. `Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstile` is the shared base — use it directly only for forms other than these auth pages.

```php
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstileLogin;

class Login extends BaseLogin
{
    use InteractsWithTurnstileLogin;

    // Optional — the trait provides a default form. When you override it,
    // keep the Turnstile component in the schema.
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
```

Register the page yourself and turn off the plugin's swap for it, otherwise the plugin replaces it with the packaged page:

```php
->login(Login::class)
->plugin(TurnstilePlugin::make()->protectLogin(false))
```

Use the login trait (not the generic `InteractsWithTurnstile`) on login pages: it keeps Turnstile off the multi-factor challenge step.

## Local / CI testing

Cloudflare test keys (any hostname, including `localhost`):

| Purpose | Site key | Secret key |
|---|---|---|
| Always passes | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Always blocks | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |
| Token already spent | — | `3x0000000000000000000000000000000AA` |

With a test site key the widget produces the dummy token `XXXX.DUMMY.TOKEN.XXXX`. The package answers the test secret keys itself, the same way Cloudflare does — verification never leaves the app, so it works offline and with `Http::preventStrayRequests()`. Production secret keys reject the dummy token.

If keys are missing, the widget is hidden and server validation is skipped so local apps without Turnstile still boot.

## Testing your app

The package adds Turnstile helpers to Livewire's test object (`Livewire::test(...)`), the same way Filament adds `fillForm()` — they work in Pest and PHPUnit alike. Each helper switches the app to the Cloudflare test keys above and fills the dummy token, so no request reaches Cloudflare.

Test the page your panel registers — the packaged one, or your own page using a trait:

```php
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Livewire\Livewire;

// Asserts: Turnstile rejects the token and stops the action.
Livewire::test(Login::class)
    ->fillForm(['email' => 'jane@example.com', 'password' => 'password'])
    ->assertTurnstileBlocks('authenticate');

// Asserts: Turnstile accepts the token and does not stop the action
// (other validation errors are not its concern).
Livewire::test(Login::class)
    ->fillForm(['email' => 'jane@example.com', 'password' => 'password'])
    ->assertTurnstileAllows('authenticate');
```

| Helper | What it does |
|---|---|
| `assertTurnstileBlocks($method)` | Fails the token, calls `$method`, asserts a Turnstile error. |
| `assertTurnstileAllows($method)` | Passes the token, calls `$method`, asserts no Turnstile error. |
| `assertTurnstileActive()` | The form shows the widget and verifies its token on submit. |
| `assertTurnstileInactive()` | The form has no widget, or it is hidden — keys missing, plugin disabled, page not protected, or the multi-factor challenge step. |
| `passTurnstile()` | Only sets up a passing token — follow with your own `call()` and assertions. |
| `failTurnstile()` | Only sets up a failing token. |

`assertTurnstileBlocks()` and `assertTurnstileAllows()` first assert the widget is active, so they fail on a page Turnstile does not protect instead of passing silently. Every helper takes the schema name as its last argument (default `form`); the two call helpers take the method's arguments before it:

```php
->assertTurnstileAllows('submit', [$ticketId], schema: 'contactForm')
```

Use `passTurnstile()` to get past Turnstile in tests about something else — the full login with a multi-factor challenge, for example:

```php
Livewire::test(Login::class)
    ->fillForm(['email' => 'jane@example.com', 'password' => 'password'])
    ->passTurnstile()
    ->call('authenticate')
    ->assertTurnstileInactive() // the challenge step has no widget
    ->fillForm(['app' => ['code' => $code]], 'multiFactorChallengeForm') // keyed by the provider id
    ->call('authenticate');
```

The helpers change the `filament-turnstile.*` keys in the config for the rest of that test.

## Configuration

```php
// config/filament-turnstile.php
return [
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
    'theme' => env('TURNSTILE_THEME', 'auto'),
    'size' => env('TURNSTILE_SIZE', 'flexible'),
    'language' => env('TURNSTILE_LANGUAGE'),
    'reset_event' => env('TURNSTILE_RESET_EVENT', 'turnstile-reset'),
];
```

## Publish views

```bash
php artisan vendor:publish --tag=filament-turnstile-views
```

## Testing this package

```bash
composer install
composer test
```

## License
MIT — see [LICENSE](LICENSE).
