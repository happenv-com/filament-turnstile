<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use RectorLaravel\Rector\Class_\EmptyGuardedPropertyToUnguardedAttributeRector;
use RectorLaravel\Rector\Class_\TablePropertyToTableAttributeRector;

/*
 * Library, not an application: no privatization and no "treat classes as
 * final" here. Apps extend a plugin's classes and override its protected
 * methods; Rector cannot see those subclasses, so narrowing visibility or
 * finalizing classes would break them without a failing test in this repository.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withComposerBased(laravel: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withPhpSets()
    ->withSkip([
        // Crashes on `is_callable([parent::class, 'onValidationError'])` in
        // src/Concerns/InteractsWithTurnstile.php (Rector cannot resolve `parent`
        // in a trait), and a path-scoped skip does not prevent it. That array is
        // a runtime check anyway, not a callable to convert.
        ArrayToFirstClassCallableRector::class,
        // Laravel 13-only model attributes; the package still supports (and CI
        // still tests) Laravel 12, where they do not exist.
        EmptyGuardedPropertyToUnguardedAttributeRector::class,
        TablePropertyToTableAttributeRector::class,
        // The Livewire test macros call each other through `$this` (the Testable),
        // with arguments Rector matches against the mixin's argument-less
        // methods and wrongly strips.
        RemoveExtraParametersRector::class => [
            __DIR__ . '/src/Testing/TestsTurnstile.php',
        ],
    ]);
