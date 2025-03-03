<?php

declare(strict_types=1);

namespace Tests\Providers\Cohere;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.cohere.api_key', env('COHERE_API_KEY', 'cothere'));
});

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('v2/embed', 'cohere/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Cohere, 'embed-english-v3.0')
        ->fromInput('Embed this sentence.')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(5);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('v2/embed', 'cohere/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Cohere, 'embed-english-v3.0')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(508);
});
