<?php

namespace Happenv\FilamentTurnstile\Tests;

use Happenv\FilamentTurnstile\FilamentTurnstileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentTurnstileServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('filament-turnstile.site_key', 'test-site-key');
        $app['config']->set('filament-turnstile.secret_key', 'test-secret-key');
        $app['config']->set('filament-turnstile.verify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    }
}
