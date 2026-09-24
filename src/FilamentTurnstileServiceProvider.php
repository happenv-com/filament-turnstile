<?php

declare(strict_types=1);

namespace Happenv\FilamentTurnstile;

use Happenv\FilamentTurnstile\Http\TurnstileVerifier;
use Happenv\FilamentTurnstile\Testing\TestsTurnstile;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTurnstileServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-turnstile';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-turnstile')
            ->hasViews('filament-turnstile')
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TurnstileVerifier::class);
    }

    public function packageBooted(): void
    {
        Testable::mixin(new TestsTurnstile);
    }
}
