<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\ValueObjects\Embedding;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.openai.api_key', env('OPENAI_API_KEY'));
});

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'openai/embeddings-input');

    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromInput('The food was delicious and the waiter...')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/openai/embeddings-input-1.json'), true);
    $embeddings = array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(8);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'openai/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/openai/embeddings-file-1.json'), true);
    $embeddings = array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(1378);
});

it('works with multiple embeddings', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'openai/embeddings-multiple-inputs');

    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromArray([
            'The food was delicious.',
            'The drinks were not so good',
        ])
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/openai/embeddings-multiple-inputs-1.json'), true);
    $embeddings = array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embeddings[0]->embedding);
    expect($response->embeddings[1]->embedding)->toBe($embeddings[1]->embedding);
    expect($response->usage->tokens)->toBe(11);
});
