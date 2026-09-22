<?php

namespace Happenv\FilamentTurnstile\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Happenv\FilamentTurnstile\Forms\Components\Turnstile;
use Happenv\FilamentTurnstile\Rules\TurnstileRule;

class TurnstileFieldTest extends TestCase
{
    public function test_rule_passes_when_verification_succeeds(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'error-codes' => [],
            ]),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'token'],
            ['cf-turnstile-response' => [new TurnstileRule]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rule_fails_when_verification_rejects_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'bad-token'],
            ['cf-turnstile-response' => [new TurnstileRule]],
        );

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('cf-turnstile-response'));
    }

    public function test_field_exposes_reset_event_and_site_key(): void
    {
        $field = Turnstile::make('cf-turnstile-response')
            ->theme('dark')
            ->size('compact')
            ->language('en-US')
            ->turnstileAction('login')
            ->resetEvent('custom-reset');

        $this->assertSame('custom-reset', $field->getResetEvent());
        $this->assertSame('dark', $field->getTheme());
        $this->assertSame('compact', $field->getSize());
        $this->assertSame('en-US', $field->getLanguage());
        $this->assertSame('login', $field->getTurnstileAction());
        $this->assertSame('1x00000000000000000000AA', $field->getSiteKey());
        $this->assertTrue($field->shouldRenderWidget());

        $view = file_get_contents(__DIR__ . '/../resources/views/components/turnstile.blade.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString('x-on:{{ $resetEvent }}.window', $view);
        $this->assertStringContainsString('turnstile.render', $view);
    }

    public function test_field_hides_when_unconfigured(): void
    {
        config([
            'filament-turnstile.site_key' => null,
            'filament-turnstile.secret_key' => null,
        ]);

        $field = Turnstile::make('cf-turnstile-response');

        $this->assertFalse($field->shouldRenderWidget());
    }
}
