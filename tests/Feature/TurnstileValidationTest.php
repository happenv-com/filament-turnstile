<?php

use Happenv\FilamentTurnstile\Pages\Auth\Login;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('asks to complete the challenge when the token is blank', function (): void {
    $errors = livewire(Login::class)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->errors();

    expect($errors->get('data.cf-turnstile-response'))
        ->toBe(['Please complete the Turnstile challenge.']);
});

it('asks to complete the challenge in the app locale', function (): void {
    app()->setLocale('pl');

    $errors = livewire(Login::class)
        ->set('data.email', 'jane@example.com')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->errors();

    expect($errors->get('data.cf-turnstile-response'))
        ->toBe(['Ukończ weryfikację Turnstile.']);
});
