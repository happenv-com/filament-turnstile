<?php

namespace Happenv\FilamentTurnstile\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Happenv\FilamentTurnstile\Http\TurnstileVerifier;
use Happenv\FilamentTurnstile\Rules\TurnstileRule;
use Happenv\FilamentTurnstile\TurnstilePlugin;

class Turnstile extends Field
{
    protected string $view = 'filament-turnstile::components.turnstile';

    protected string | Closure | null $theme = null;

    protected string | Closure | null $size = null;

    protected string | Closure | null $language = null;

    protected string | Closure | null $turnstileAction = null;

    protected string | Closure | null $resetEvent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->dehydrated(false);

        $this->hiddenLabel();

        $this->visible(fn (): bool => $this->shouldRenderWidget());

        $this->required(fn (): bool => $this->shouldRenderWidget());

        $this->rule(fn (): TurnstileRule => new TurnstileRule(
            app(TurnstileVerifier::class),
        ));

        $this->validationAttribute(__('filament-turnstile::validation.missing-input-response'));
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

    public function turnstileAction(string | Closure | null $action): static
    {
        $this->turnstileAction = $action;

        return $this;
    }

    public function resetEvent(string | Closure | null $resetEvent): static
    {
        $this->resetEvent = $resetEvent;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->evaluate($this->theme)
            ?? $this->pluginTheme()
            ?? (string) config('filament-turnstile.theme', 'auto');
    }

    public function getSize(): string
    {
        return $this->evaluate($this->size)
            ?? $this->pluginSize()
            ?? (string) config('filament-turnstile.size', 'flexible');
    }

    public function getLanguage(): ?string
    {
        return $this->evaluate($this->language)
            ?? $this->pluginLanguage()
            ?? config('filament-turnstile.language');
    }

    public function getTurnstileAction(): ?string
    {
        return $this->evaluate($this->turnstileAction);
    }

    public function getResetEvent(): string
    {
        return $this->evaluate($this->resetEvent)
            ?? (string) config('filament-turnstile.reset_event', 'turnstile-reset');
    }

    public function getSiteKey(): ?string
    {
        return app(TurnstileVerifier::class)->siteKey();
    }

    public function shouldRenderWidget(): bool
    {
        if (! app(TurnstileVerifier::class)->isConfigured()) {
            return false;
        }

        if ($this->pluginIsEnabled() === false) {
            return false;
        }

        return true;
    }

    protected function pluginTheme(): ?string
    {
        return $this->plugin()?->getTheme();
    }

    protected function pluginSize(): ?string
    {
        return $this->plugin()?->getSize();
    }

    protected function pluginLanguage(): ?string
    {
        return $this->plugin()?->getLanguage();
    }

    protected function pluginIsEnabled(): ?bool
    {
        return $this->plugin()?->isEnabled();
    }

    protected function plugin(): ?TurnstilePlugin
    {
        try {
            return TurnstilePlugin::get();
        } catch (\Throwable) {
            return null;
        }
    }
}
