<?php

declare(strict_types=1);

use Happenv\FilamentTurnstile\Tests\PanelTestCase;
use Happenv\FilamentTurnstile\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');

pest()->extend(PanelTestCase::class)->in('Feature', 'Browser');
