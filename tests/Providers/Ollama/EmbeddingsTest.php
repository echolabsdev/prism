<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Embedding;
use Tests\Fixtures\FixtureResponse;

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromInput('The food was delicious and the waiter...')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-input-1.json'), true);
    $embeddings = array_map(fn (array $item): \EchoLabs\Prism\ValueObjects\Embedding => Embedding::fromArray($item), data_get($embeddings, 'embeddings'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(10);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-file-1.json'), true);
    $embeddings = array_map(fn (array $item): \EchoLabs\Prism\ValueObjects\Embedding => Embedding::fromArray($item), data_get($embeddings, 'embeddings'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(512);
});

it('works with multiple embeddings', function (): void {
    FixtureResponse::fakeResponseSequence('api/embed', 'ollama/embeddings-multiple-inputs');

    $response = Prism::embeddings()
        ->using(Provider::Ollama, 'mxbai-embed-large')
        ->fromArray([
            'The food was delicious.',
            'The drinks were not so good',
        ])
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/ollama/embeddings-multiple-inputs-1.json'), true);
    $embeddings = array_map(fn (array $item): \EchoLabs\Prism\ValueObjects\Embedding => Embedding::fromArray($item), data_get($embeddings, 'embeddings'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->embeddings[1]->embedding)->toBe($embeddings[1]->embedding);
    expect($response->usage->tokens)->toBe(522);
});
