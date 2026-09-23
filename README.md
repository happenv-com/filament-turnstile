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

Extend the packaged pages or use the trait:

```php
use Happenv\FilamentTurnstile\Pages\Auth\Login as TurnstileLogin;

class Login extends TurnstileLogin
{
    //
}
```

## Local / CI testing

Cloudflare test keys (any hostname, including `localhost`):

| Purpose | Site key | Secret key |
|---|---|---|
| Always passes | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Always blocks | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |

If keys are missing, the widget is hidden and server validation is skipped so local apps without Turnstile still boot.

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
