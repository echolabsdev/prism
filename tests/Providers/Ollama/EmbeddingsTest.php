<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'ollama/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromInput('The food was delicious and the waiter...')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(10);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'ollama/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(512);
});
