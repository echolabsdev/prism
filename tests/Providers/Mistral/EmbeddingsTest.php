<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.mistral.api_key', env('MISTRAL_API_KEY', 'sk-1234'));
});
it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'mistral/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Mistral, 'mistral-small-latest')
        ->fromInput('Embed this sentence.')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(25);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'mistral/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Mistral, 'mistral-embed')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(1174);
});
