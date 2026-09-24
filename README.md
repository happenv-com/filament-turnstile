# Filament Turnstile

[![Latest Version](https://img.shields.io/github/v/release/happenv-com/filament-turnstile?style=flat-square&label=version)](https://github.com/happenv-com/filament-turnstile/releases)
[![Tests](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-turnstile/tests.yml?label=tests&style=flat-square)](https://github.com/happenv-com/filament-turnstile/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-turnstile/phpstan.yml?label=phpstan&style=flat-square)](https://github.com/happenv-com/filament-turnstile/actions/workflows/phpstan.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/happenv-com/filament-turnstile/quality.yml?label=code%20quality&style=flat-square)](https://github.com/happenv-com/filament-turnstile/actions/workflows/quality.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/happenv-com/filament-turnstile.svg?style=flat-square)](https://packagist.org/packages/happenv-com/filament-turnstile)
[![License](https://img.shields.io/github/license/happenv-com/filament-turnstile.svg?style=flat-square)](LICENSE.md)

Cloudflare Turnstile panel plugin for **Filament v4 and v5**. Register it once on a panel and login is protected automatically — optionally registration and password-reset request too. No form schema edits for the common case.

Based on [muazzambuilds/filament-turnstile](https://github.com/muazzambuilds/filament-turnstile) by Muazzam Builds.

```php
use Happenv\FilamentTurnstile\TurnstilePlugin;

$panel->plugin(TurnstilePlugin::make());
```

## Key features

- **Login, registration and password reset** — one panel plugin protects Filament's auth pages; each page is switched on or off on its own. See [Panel plugin](#panel-plugin-recommended).
- **Multi-factor authentication aware** — Turnstile runs on the password step only, never again on the app code or passkey challenge.
- **Custom auth pages** — one trait per page (`Login`, `Register`, `RequestPasswordReset`) adds Turnstile to your own subclasses, plus a `Turnstile` form field for any other Filament form. See [Custom auth pages](#custom-auth-pages).
- **Testing helpers** — Livewire test mixins (`assertTurnstileBlocks()`, `assertTurnstileAllows()`, `passTurnstile()`, …) for Pest and PHPUnit, with Cloudflare's test keys answered locally, so tests never reach Cloudflare. See [Testing your application](#testing-your-application).
- **Laravel 11, 12 and 13, Filament 4 and 5** — see [Requirements](#requirements).
- **Translations** — validation messages in 64 languages, every locale Filament ships. See [Supported languages](#supported-languages).
- **Safe without keys** — with no keys configured the widget is hidden and verification is skipped, so local apps still boot.

## Requirements

| Package  | Versions      |
|----------|---------------|
| PHP      | 8.2 – 8.5     |
| Laravel  | 11, 12, 13    |
| Filament | 4, 5          |

CI runs the test suite on PHP 8.3 – 8.5 with Laravel 12 and 13 and Filament 4 and 5. PHP 8.2 and Laravel 11 are allowed by `composer.json` but cannot be tested in CI (the test tooling needs PHP 8.3, and Composer refuses every Laravel 11 release because of security advisories).

## Installation

Install the package via Composer:

```bash
composer require happenv-com/filament-turnstile
```

Add your keys to `.env`:

```env
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

Create a widget and get keys at [dash.cloudflare.com](https://dash.cloudflare.com) → **Turnstile**.

Register the plugin in your panel provider — see [Panel plugin](#panel-plugin-recommended) below.

The widget's wrapper uses a few Tailwind classes. If your panel uses a [custom theme](https://filamentphp.com/docs/5.x/styling/overview#creating-a-custom-theme), add the package's views to its CSS file so Tailwind generates them:

```css
@source '../../../../vendor/happenv-com/filament-turnstile/resources/**/*.blade.php';
```

## Configuration

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=filament-turnstile-config
```

```php
// config/filament-turnstile.php
return [
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
    'connect_timeout' => (int) env('TURNSTILE_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('TURNSTILE_TIMEOUT', 10),
    'theme' => env('TURNSTILE_THEME', 'auto'),
    'size' => env('TURNSTILE_SIZE', 'flexible'),
    'language' => env('TURNSTILE_LANGUAGE'),
    'reset_event' => env('TURNSTILE_RESET_EVENT', 'turnstile-reset'),
];
```

Optionally, publish the views:

```bash
php artisan vendor:publish --tag=filament-turnstile-views
```

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

Keep extending Filament's page and add the matching trait from `Happenv\FilamentTurnstile\Concerns` — it carries everything the packaged page does:

| Your page extends | Use trait |
|---|---|
| `Login` | `InteractsWithTurnstileLogin` |
| `Register` | `InteractsWithTurnstileRegistration` |
| `RequestPasswordReset` | `InteractsWithTurnstilePasswordReset` |

What each trait adds:

- **Every trait** gives the page a default form with the widget and resets the widget on any validation error.
- **`InteractsWithTurnstileLogin`** also resets the widget after a wrong password and skips it on the multi-factor challenge step (app codes, passkeys). The token was already verified on the password step, and Cloudflare accepts a token only once.
- **`InteractsWithTurnstileRegistration`** also keeps the token out of the data passed to user creation.
- **`InteractsWithTurnstilePasswordReset`** also resets the widget after each request, so another one can be sent.

`InteractsWithTurnstile` is the shared base — use it directly only for forms other than these auth pages.

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

## Testing your application

### Local and CI keys

Cloudflare test keys (any hostname, including `localhost`):

| Purpose | Site key | Secret key |
|---|---|---|
| Always passes | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Always blocks | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |
| Token already spent | — | `3x0000000000000000000000000000000AA` |

With a test site key the widget produces the dummy token `XXXX.DUMMY.TOKEN.XXXX`. The package answers the test secret keys itself, the same way Cloudflare does — verification never leaves the app, so it works offline and with `Http::preventStrayRequests()`. Production secret keys reject the dummy token.

If keys are missing, the widget is hidden and server validation is skipped so local apps without Turnstile still boot.

### Livewire test helpers

The package adds Turnstile helpers to Livewire's test object (`Livewire::test(...)`, or `livewire(...)` from [pest-plugin-livewire](https://pestphp.com/docs/plugins#livewire)), the same way Filament adds `fillForm()` — they work in Pest and PHPUnit alike. Each helper switches the app to the Cloudflare test keys above and fills the dummy token, so no request reaches Cloudflare.

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

### Browser tests

The helpers above extend Livewire's test object; [Pest's browser pages](https://pestphp.com/docs/browser-testing) cannot be extended the same way. In a browser test, switch to the test keys yourself — Pest serves the app from the test process, so the config applies to the page. The real widget then solves itself; wait for its token before submitting:

```php
use Happenv\FilamentTurnstile\Testing\TestsTurnstile;

it('logs in through Turnstile', function () {
    config([
        'filament-turnstile.site_key' => TestsTurnstile::PASSING_SITE_KEY,
        'filament-turnstile.secret_key' => TestsTurnstile::PASSING_SECRET_KEY, // BLOCKING_SECRET_KEY to reject the token
    ]);

    visit('/admin/login')
        ->assertScript(
            "document.querySelector('.fi-fo-turnstile [name=\"cf-turnstile-response\"]')?.value",
            TestsTurnstile::DUMMY_TOKEN,
        )
        ->fill('[id="form.email"]', 'jane@example.com')
        ->fill('[id="form.password"]', 'password')
        ->submit()
        ->assertPathIs('/admin');
});
```

The widget loads from `challenges.cloudflare.com`, so browser tests need network access.

## Translations

Validation messages ship in every locale Filament ships — see [Supported languages](#supported-languages). The app locale picks the language.

To change a message or add a language, publish the files:

```bash
php artisan vendor:publish --tag=filament-turnstile-translations
```

The widget itself follows the browser language; set `->language()` or `TURNSTILE_LANGUAGE` to fix it.

### Supported languages

| Language | Language | Language | Language |
|---|---|---|---|
| Amharic `am` | Persian `fa` | Lithuanian `lt` | Slovenian `sl` |
| Arabic `ar` | Finnish `fi` | Mizo `lus` | Albanian `sq` |
| Azerbaijani `az` | Filipino `fil` | Latvian `lv` | Serbian (Cyrillic) `sr_Cyrl` |
| Bulgarian `bg` | French `fr` | Macedonian `mk` | Serbian (Latin) `sr_Latn` |
| Bengali `bn` | Hebrew `he` | Mongolian `mn` | Swedish `sv` |
| Bosnian `bs` | Hindi `hi` | Malay `ms` | Swahili `sw` |
| Catalan `ca` | Croatian `hr` | Burmese `my` | Tajik `tg` |
| Central Kurdish `ckb` | Hungarian `hu` | Norwegian Bokmål `nb` | Thai `th` |
| Czech `cs` | Armenian `hy` | Nepali `ne` | Turkish `tr` |
| Danish `da` | Indonesian `id` | Dutch `nl` | Ukrainian `uk` |
| German `de` | Italian `it` | Polish `pl` | Urdu `ur` |
| Greek `el` | Japanese `ja` | Portuguese `pt` | Uzbek `uz` |
| English `en` | Georgian `ka` | Portuguese (Brazil) `pt_BR` | Vietnamese `vi` |
| Spanish `es` | Khmer `km` | Romanian `ro` | Chinese (Simplified) `zh_CN` |
| Estonian `et` | Korean `ko` | Russian `ru` | Chinese (Hong Kong) `zh_HK` |
| Basque `eu` | Kurdish `ku` | Slovak `sk` | Chinese (Traditional) `zh_TW` |

## Development

```bash
composer test          # unit and feature tests
composer test-browser  # browser tests (once: npm ci && npx playwright install chromium)
composer phpstan       # static analysis
composer cs            # fix code style: composer normalize, Rector, Pint
composer ci            # everything CI checks, locally
```

The browser tests drive the real widget from `challenges.cloudflare.com` with Cloudflare's test keys, so they need network access.

## Upgrading

Breaking changes and how to migrate are described in [UPGRADING](UPGRADING.md) for every major version.

## Changelog

See [CHANGELOG](CHANGELOG.md) and [GitHub releases](https://github.com/happenv-com/filament-turnstile/releases) for what has changed recently.

## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Happenv sp. z o.o.](https://happenv.com)
- [webard](https://github.com/webard)
- [Muazzam Builds](https://github.com/muazzambuilds) — author of the original [muazzambuilds/filament-turnstile](https://github.com/muazzambuilds/filament-turnstile)
- [All contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

---

<p align="center">
    <a href="https://happenv.com">
        <img src="art/happenv-logo.png" alt="Happenv" width="400">
    </a>
</p>
