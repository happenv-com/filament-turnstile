---
name: filament-turnstile-development
description: >-
  Protect Filament forms with Cloudflare Turnstile using happenv-com/filament-turnstile. Use when
  registering TurnstilePlugin (protectLogin(), protectRegistration(), protectPasswordReset(),
  enabled(), theme/size/language), writing a custom Login / Register / RequestPasswordReset page
  with InteractsWithTurnstileLogin / InteractsWithTurnstileRegistration /
  InteractsWithTurnstilePasswordReset, adding the Turnstile form field to any other Filament form
  or Livewire component (InteractsWithTurnstile, dispatchTurnstileReset(), TurnstileRule), or
  testing with passTurnstile(), failTurnstile(), assertTurnstileBlocks(), assertTurnstileAllows(),
  assertTurnstileActive() / assertTurnstileInactive() and the Cloudflare test keys.
---

# Filament Turnstile

A Filament panel plugin that puts a Cloudflare Turnstile widget on the panel's auth pages (login by default, registration and password-reset request on demand) by swapping in subclasses of Filament's pages. The same `Turnstile` form field and traits protect any other Filament form. The token is verified server-side through a validation rule; Cloudflare accepts each token only once.

## When to use this skill

- Adding Turnstile to a panel, or choosing which auth pages it protects.
- The app already has its own `Login` / `Register` / `RequestPasswordReset` page and needs Turnstile on it.
- Protecting a non-auth form: a contact form, a public signup, a Livewire component with a Filament schema.
- Writing tests for protected pages, or tests that must get past Turnstile to test something else.

## Installation

```bash
composer require happenv-com/filament-turnstile
```

```env
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
```

With a custom panel theme, add the views to the theme CSS so Tailwind sees their classes:

```css
@source '../../../../vendor/happenv-com/filament-turnstile/resources/**/*.blade.php';
```

Config (`--tag=filament-turnstile-config`): `site_key`, `secret_key`, `verify_url`, `connect_timeout` (5), `timeout` (10), `theme` (`auto|light|dark`), `size` (`normal|flexible|compact`), `language`, `reset_event` (`turnstile-reset`).

## Panel plugin

```php
use Happenv\FilamentTurnstile\TurnstilePlugin;

return $panel
    ->login()
    ->registration()
    ->passwordReset()
    ->plugin(                               // AFTER login()/registration()/passwordReset()
        TurnstilePlugin::make()
            ->protectLogin()                // default true
            ->protectRegistration()         // default false
            ->protectPasswordReset()        // default false; request page only
            ->theme('auto')->size('flexible')->language('en-US')
            ->enabled(fn (): bool => ! app()->isLocal()),
    );
```

- `register()` replaces the page class: `->login(Happenv\FilamentTurnstile\Pages\Auth\Login::class)`, likewise `Register` and `RequestPasswordReset`. It only does so when the panel already has that page, which is why `->plugin()` must come after `->login()` etc.
- Every toggle accepts `bool|Closure`.
- `enabled(false)` hides the widget (and so skips validation) on every `Turnstile` field rendered in that panel, custom forms included.
- Field options resolve in this order: the field's own `->theme()` etc., then the current panel's plugin, then config.

## Custom auth pages

If the app registers its own page, **turn off the plugin's swap for it**, otherwise the plugin silently replaces the app's page with the packaged one:

```php
->login(App\Filament\Pages\Auth\Login::class)
->plugin(TurnstilePlugin::make()->protectLogin(false))
```

Then add the matching trait to the page:

| Page extends (Filament) | Trait (`Happenv\FilamentTurnstile\Concerns\`) | Turnstile action |
|---|---|---|
| `Filament\Auth\Pages\Login` | `InteractsWithTurnstileLogin` | `login` |
| `Filament\Auth\Pages\Register` | `InteractsWithTurnstileRegistration` | `register` |
| `Filament\Auth\Pages\PasswordReset\RequestPasswordReset` | `InteractsWithTurnstilePasswordReset` | `password-reset` |

```php
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstileLogin;

class Login extends BaseLogin
{
    use InteractsWithTurnstileLogin;

    public function form(Schema $schema): Schema   // optional: the trait already defines form()
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
            $this->getTurnstileFormComponent(),    // keep this exact call
        ]);
    }
}
```

What the traits do and why the details matter:

- All of them define `form()` with the widget and reset it on every validation error.
- **Login**: `getTurnstileFormComponent()` hides the field once the user is on the multi-factor challenge step (for the same user). Filament re-validates the login form when the challenge is submitted, and the token is already spent, so a plain `Turnstile::make(...)` on a login page would fail every MFA sign-in with `timeout-or-duplicate`. Always use `getTurnstileFormComponent()` there and the login trait, not `InteractsWithTurnstile`. It also resets the widget after a wrong password.
- **Registration**: removes `cf-turnstile-response` in `mutateFormDataBeforeRegister()`. If you override that method, call `parent::` or keep the token out of user creation yourself.
- **Password reset**: resets the widget after every `request()`, so a second e-mail can be requested.

## Any other form

The field is a normal Filament field:

```php
use Happenv\FilamentTurnstile\Forms\Components\Turnstile;

Turnstile::make('cf-turnstile-response')
    ->turnstileAction('contact')   // label shown in Cloudflare analytics; not checked server-side
    ->theme('dark')->size('compact')->language('pl')
    ->resetEvent('turnstile-reset');
```

- It is `required` and carries `TurnstileRule` only while the widget renders. It is `dehydrated(false)`: the token is never in `$this->form->getState()`, so it is never saved to a model.
- A token is single use. After a failed validation or a successful submit that leaves the form on screen, the widget must be reset or the next submit fails. Use the trait on the component to get `dispatchTurnstileReset()` and the automatic reset on validation errors.

On a Filament `Page` (extends `Filament\Pages\Page`), just `use InteractsWithTurnstile;`.

On a plain Livewire component with `InteractsWithSchemas` (or `InteractsWithForms`), define `onValidationError()` in the class yourself. Both traits declare it, so without your own method PHP stops with a trait collision; and the trait's version, picked with `insteadof`, forwards to `parent::onValidationError()`, which Livewire's `Component` answers with a `BadMethodCallException` on the first failed validation.

```php
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Happenv\FilamentTurnstile\Concerns\InteractsWithTurnstile;
use Illuminate\Validation\ValidationException;

class ContactForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use InteractsWithTurnstile;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->email()->required(),
            Textarea::make('message')->required(),
            $this->getTurnstileFormComponent('contact'),   // default action is 'login'
        ])->statePath('data');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->dispatchTurnstileReset();
    }

    public function submit(): void
    {
        $data = $this->form->getState();   // validates Turnstile, no token in $data
        // ... send
        $this->form->fill();
        $this->dispatchTurnstileReset();
    }
}
```

Outside Filament schemas, `Happenv\FilamentTurnstile\Rules\TurnstileRule` is a plain Laravel `ValidationRule` (`['cf-turnstile-response' => ['required', new TurnstileRule]]`), but you then render the widget yourself.

## Behaviour to know

- **No keys, no protection.** If `site_key` or `secret_key` is empty, the widget is hidden and verification passes. That keeps local apps booting, and it also means a missing env var in production silently disables Turnstile. Check the keys in deployment.
- **Fail closed on Cloudflare errors.** A connection error, a timeout or a non-2xx answer from siteverify fails validation (`internal-error`).
- The rule sends `request()->ip()` as `remoteip`; behind a proxy or Cloudflare, configure trusted proxies so it is the visitor's IP.
- The widget script is loaded once per page from `challenges.cloudflare.com` (`render=explicit`); a strict CSP must allow it.
- Messages are translated per Cloudflare error code (`filament-turnstile::validation.<code>`, 64 locales); unknown codes fall back to `validation.failed`. The widget's own language follows the browser unless `language()` is set.

## Testing

The package mixes helpers into Livewire's `Testable` (works with `Livewire::test()` and Pest's `livewire()`). Each switches the config to Cloudflare's test keys and fills the dummy token; the verifier answers test secret keys locally, so nothing reaches Cloudflare (safe with `Http::preventStrayRequests()`).

```php
use Happenv\FilamentTurnstile\Pages\Auth\Login;   // or the app's own page

Livewire::test(Login::class)
    ->fillForm(['email' => 'jane@example.com', 'password' => 'password'])
    ->assertTurnstileBlocks('authenticate');

Livewire::test(Login::class)
    ->fillForm(['email' => 'jane@example.com', 'password' => 'password'])
    ->assertTurnstileAllows('authenticate');

// Get past Turnstile when testing something else:
Livewire::test(Login::class)
    ->fillForm(['email' => $user->email, 'password' => 'password'])
    ->passTurnstile()
    ->call('authenticate')
    ->assertTurnstileInactive()                                   // MFA step has no widget
    ->fillForm(['app' => ['code' => $code]], 'multiFactorChallengeForm')
    ->call('authenticate');

// Custom component and schema:
Livewire::test(ContactForm::class)
    ->fillForm([...])
    ->assertTurnstileAllows('submit', [], schema: 'form');
```

| Helper | Does |
|---|---|
| `passTurnstile($schema = 'form')` / `failTurnstile(...)` | Only sets up a passing / failing token. |
| `assertTurnstileAllows($method, $params = [], $schema = 'form')` | Passing token, calls `$method`, asserts no Turnstile error. |
| `assertTurnstileBlocks($method, $params = [], $schema = 'form')` | Failing token, calls `$method`, asserts a Turnstile error. |
| `assertTurnstileActive($schema)` / `assertTurnstileInactive($schema)` | Widget shown and verified / absent or hidden. |

- `Allows` / `Blocks` first assert the widget is active, so they fail on an unprotected page instead of passing silently.
- The helpers change `filament-turnstile.*` config for the rest of the test. Set `site_key`/`secret_key` to `null` in a test to check the "no keys" behaviour.
- Test keys are constants on `Happenv\FilamentTurnstile\Testing\TestsTurnstile`: `PASSING_SITE_KEY`, `PASSING_SECRET_KEY`, `BLOCKING_SITE_KEY`, `BLOCKING_SECRET_KEY`, `DUMMY_TOKEN`. Secret `3x0000000000000000000000000000000AA` answers "token already spent".
- Browser tests (Pest browser): the helpers do not apply; set the config to the test keys yourself, wait until `.fi-fo-turnstile [name="cf-turnstile-response"]` holds `TestsTurnstile::DUMMY_TOKEN`, then submit. They need network access.

## Do not

- Do not call `->plugin(TurnstilePlugin::make())` before `->login()` / `->registration()` / `->passwordReset()`.
- Do not register your own auth page without `protectLogin(false)` (or the matching toggle); the plugin replaces it.
- Do not put a bare `Turnstile::make()` on a login page or use `InteractsWithTurnstile` there; MFA sign-ins will fail.
- Do not rely on `turnstileAction()` for security; the server does not check the action.
- Do not leave a form on screen after a submit without `dispatchTurnstileReset()`.
