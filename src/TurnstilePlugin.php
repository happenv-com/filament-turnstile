<?php

namespace Happenv\FilamentTurnstile;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Happenv\FilamentTurnstile\Pages\Auth\PasswordReset\RequestPasswordReset;
use Happenv\FilamentTurnstile\Pages\Auth\Register;

class TurnstilePlugin implements Plugin
{
    protected bool | Closure $protectLogin = true;

    protected bool | Closure $protectRegistration = false;

    protected bool | Closure $protectPasswordReset = false;

    protected string | Closure | null $theme = null;

    protected string | Closure | null $size = null;

    protected string | Closure | null $language = null;

    protected bool | Closure $enabled = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-turnstile';
    }

    public function register(Panel $panel): void
    {
        if ($this->shouldProtectLogin() && $panel->hasLogin()) {
            $panel->login(Login::class);
        }

        if ($this->shouldProtectRegistration() && $panel->hasRegistration()) {
            $panel->registration(Register::class);
        }

        if ($this->shouldProtectPasswordReset() && $panel->hasPasswordReset()) {
            $panel->passwordReset(
                RequestPasswordReset::class,
                $panel->getResetPasswordRouteAction()
                    ?? \Filament\Auth\Pages\PasswordReset\ResetPassword::class,
            );
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function protectLogin(bool | Closure $condition = true): static
    {
        $this->protectLogin = $condition;

        return $this;
    }

    public function protectRegistration(bool | Closure $condition = true): static
    {
        $this->protectRegistration = $condition;

        return $this;
    }

    public function protectPasswordReset(bool | Closure $condition = true): static
    {
        $this->protectPasswordReset = $condition;

        return $this;
    }

    public function theme(string | Closure | null $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function size(string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function language(string | Closure | null $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function enabled(bool | Closure $condition = true): static
    {
        $this->enabled = $condition;

        return $this;
    }

    public function shouldProtectLogin(): bool
    {
        return (bool) $this->evaluate($this->protectLogin);
    }

    public function shouldProtectRegistration(): bool
    {
        return (bool) $this->evaluate($this->protectRegistration);
    }

    public function shouldProtectPasswordReset(): bool
    {
        return (bool) $this->evaluate($this->protectPasswordReset);
    }

    public function getTheme(): ?string
    {
        $theme = $this->evaluate($this->theme);

        return filled($theme) ? (string) $theme : null;
    }

    public function getSize(): ?string
    {
        $size = $this->evaluate($this->size);

        return filled($size) ? (string) $size : null;
    }

    public function getLanguage(): ?string
    {
        $language = $this->evaluate($this->language);

        return filled($language) ? (string) $language : null;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->evaluate($this->enabled);
    }

    protected function evaluate(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
