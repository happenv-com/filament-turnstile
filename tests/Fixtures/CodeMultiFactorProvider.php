<?php

namespace Happenv\FilamentTurnstile\Tests\Fixtures;

use Closure;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Auth\Authenticatable;

class CodeMultiFactorProvider implements MultiFactorAuthenticationProvider
{
    public const CODE = '123456';

    public function isEnabled(Authenticatable $user): bool
    {
        return true;
    }

    public function getId(): string
    {
        return 'code';
    }

    public function getLoginFormLabel(): string
    {
        return 'Code';
    }

    public function getManagementSchemaComponents(): array
    {
        return [];
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            TextInput::make('code')
                ->required()
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== self::CODE) {
                        $fail('Invalid code.');
                    }
                }),
        ];
    }
}
