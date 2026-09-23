<?php

dataset('locales', function (): array {
    $locales = array_map('basename', glob(__DIR__ . '/../../resources/lang/*', GLOB_ONLYDIR));

    return array_values(array_diff($locales, ['en']));
});

it('translates every validation message', function (string $locale): void {
    $english = require __DIR__ . '/../../resources/lang/en/validation.php';
    $translated = require __DIR__ . "/../../resources/lang/{$locale}/validation.php";

    expect(array_keys($translated))->toEqualCanonicalizing(array_keys($english))
        ->and($translated)->each->toBeString()->not->toBeEmpty();
})->with('locales');

it('shows the message in the app locale', function (): void {
    app()->setLocale('pl');

    expect(__('filament-turnstile::validation.missing-input-response'))
        ->toBe('Ukończ weryfikację Turnstile.');
});
