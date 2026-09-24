<?php

namespace Happenv\FilamentTurnstile\Rules;

use Closure;
use Happenv\FilamentTurnstile\Http\TurnstileVerifier;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileRule implements ValidationRule
{
    public function __construct(
        protected ?TurnstileVerifier $verifier = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = $this->verifier ?? app(TurnstileVerifier::class);

        if (! $verifier->isConfigured()) {
            return;
        }

        $result = $verifier->verify(
            is_string($value) ? $value : null,
            request()->ip(),
        );

        if ($result['success'] ?? false) {
            return;
        }

        $errorCodes = $result['error-codes'] ?? [];

        if ($errorCodes === []) {
            $fail(__('filament-turnstile::validation.failed'));

            return;
        }

        foreach ($errorCodes as $errorCode) {
            $key = "filament-turnstile::validation.{$errorCode}";
            $message = __($key);

            $fail($message === $key ? __('filament-turnstile::validation.failed') : $message);
        }
    }
}
