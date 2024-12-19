<?php

use EchoLabs\Prism\Prism;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.anthropic.api_key', env('ANTHROPIC_API_KEY', 'sk-1234'));
});

it('does not set the anthropic-beta header if no beta feature is enabled in config', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-a-prompt');

    Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Who are you?')
        ->generate();

    Http::assertSent(fn (Request $request) => $request->hasHeader('anthropic-beta') === false);
});

it('sends the anthropic-beta header if a beta feature is enabled in config', function (): void {
    config()->set('prism.providers.anthropic.beta_features', 'prompt-caching-2024-07-31');

    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-a-prompt');

    Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Who are you?')
        ->generate();

    Http::assertSent(fn (Request $request) => $request->hasHeader('anthropic-beta', 'prompt-caching-2024-07-31'));
});
