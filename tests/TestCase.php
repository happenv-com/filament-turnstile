<?php

namespace Happenv\FilamentTurnstile\Tests;

use ErrorException;
use Happenv\FilamentTurnstile\FilamentTurnstileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Laravel only logs deprecations. Fail the test when the package's OWN
        // code triggers one, so it is fixed before the next PHP / Laravel /
        // Filament release turns it into an error.
        $sourcePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $previousHandler = set_error_handler(function (int $level, string $message, string $file = '', int $line = 0) use (&$previousHandler, $sourcePath): bool {
            if (in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true) && str_starts_with($file, $sourcePath)) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }

            return $previousHandler && (bool) $previousHandler($level, $message, $file, $line);
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }

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
