<?php

namespace Happenv\FilamentTurnstile\Testing;

use Closure;
use Filament\Schemas\Contracts\HasSchemas;
use Happenv\FilamentTurnstile\Forms\Components\Turnstile;
use Illuminate\Testing\Assert;
use Livewire\Features\SupportTesting\Testable;

/**
 * Livewire test helpers. Each one switches the app to Cloudflare's test keys,
 * which the verifier answers locally — no request reaches Cloudflare.
 *
 * @method HasSchemas instance()
 *
 * @mixin Testable
 */
class TestsTurnstile
{
    public const PASSING_SITE_KEY = '1x00000000000000000000AA';

    public const PASSING_SECRET_KEY = '1x0000000000000000000000000000000AA';

    public const BLOCKING_SITE_KEY = '2x00000000000000000000AB';

    public const BLOCKING_SECRET_KEY = '2x0000000000000000000000000000000AA';

    public const DUMMY_TOKEN = 'XXXX.DUMMY.TOKEN.XXXX';

    /**
     * Make the next Turnstile verification pass.
     */
    public function passTurnstile(): Closure
    {
        return function (string $schema = 'form'): static {
            config([
                'filament-turnstile.site_key' => TestsTurnstile::PASSING_SITE_KEY,
                'filament-turnstile.secret_key' => TestsTurnstile::PASSING_SECRET_KEY,
            ]);

            /** @phpstan-ignore-next-line */
            $this->set($this->getTurnstileComponent($schema)->getStatePath(), TestsTurnstile::DUMMY_TOKEN);

            return $this;
        };
    }

    /**
     * Make the next Turnstile verification fail.
     */
    public function failTurnstile(): Closure
    {
        return function (string $schema = 'form'): static {
            config([
                'filament-turnstile.site_key' => TestsTurnstile::BLOCKING_SITE_KEY,
                'filament-turnstile.secret_key' => TestsTurnstile::BLOCKING_SECRET_KEY,
            ]);

            /** @phpstan-ignore-next-line */
            $this->set($this->getTurnstileComponent($schema)->getStatePath(), TestsTurnstile::DUMMY_TOKEN);

            return $this;
        };
    }

    /**
     * Calls the action with a passing token and asserts Turnstile did not stop it.
     */
    public function assertTurnstileAllows(): Closure
    {
        return function (string $method, array $parameters = [], string $schema = 'form'): static {
            /** @phpstan-ignore-next-line */
            $this->passTurnstile($schema)->assertTurnstileActive($schema);

            /** @phpstan-ignore-next-line */
            $statePath = $this->getTurnstileComponent($schema)->getStatePath();

            $this->call($method, ...$parameters)->assertHasNoErrors([$statePath]);

            return $this;
        };
    }

    /**
     * Calls the action with a failing token and asserts Turnstile stopped it.
     */
    public function assertTurnstileBlocks(): Closure
    {
        return function (string $method, array $parameters = [], string $schema = 'form'): static {
            /** @phpstan-ignore-next-line */
            $this->failTurnstile($schema)->assertTurnstileActive($schema);

            /** @phpstan-ignore-next-line */
            $statePath = $this->getTurnstileComponent($schema)->getStatePath();

            $this->call($method, ...$parameters)->assertHasErrors([$statePath]);

            return $this;
        };
    }

    /**
     * Asserts the form shows the widget and will verify its token on submit.
     */
    public function assertTurnstileActive(): Closure
    {
        return function (string $schema = 'form'): static {
            /** @phpstan-ignore-next-line */
            $component = $this->getTurnstileComponent($schema);

            Assert::assertFalse(
                $component->isHidden(),
                "Turnstile in the [{$schema}] schema of [" . $this->instance()::class . '] is not active.',
            );

            return $this;
        };
    }

    /**
     * Asserts the form neither shows the widget nor verifies a token — either
     * it has no Turnstile component, or the component is hidden.
     */
    public function assertTurnstileInactive(): Closure
    {
        return function (string $schema = 'form'): static {
            /** @phpstan-ignore-next-line */
            $component = $this->findTurnstileComponent($schema);

            Assert::assertTrue(
                $component?->isHidden() ?? true,
                "Turnstile in the [{$schema}] schema of [" . $this->instance()::class . '] is active.',
            );

            return $this;
        };
    }

    protected function getTurnstileComponent(): Closure
    {
        return function (string $schema): Turnstile {
            /** @phpstan-ignore-next-line */
            $component = $this->findTurnstileComponent($schema);

            Assert::assertNotNull(
                $component,
                "The [{$schema}] schema of [" . $this->instance()::class . '] has no Turnstile component.',
            );

            return $component;
        };
    }

    protected function findTurnstileComponent(): Closure
    {
        return function (string $schema): ?Turnstile {
            /** @var ?Turnstile $component */
            $component = $this->instance()->getSchema($schema)?->getComponent(
                fn (mixed $component): bool => $component instanceof Turnstile,
                withActions: false,
                withHidden: true,
            );

            return $component;
        };
    }
}
