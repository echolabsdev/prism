<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.gemini.api_key', env('GEMINI_API_KEY', 'gk-1234'));
});

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('models/text-embedding-004:embedContent', 'gemini/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Gemini, 'text-embedding-004')
        ->fromInput('Embed this sentence.')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(0); // Gemini doesn't provide token usage
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('models/text-embedding-004:embedContent', 'gemini/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Gemini, 'text-embedding-004')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(0); // Gemini doesn't provide token usage
});
