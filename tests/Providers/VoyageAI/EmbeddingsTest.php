<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Embedding;
use Tests\Fixtures\FixtureResponse;

it('returns embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'voyageai/embeddings-from-input');

    $response = Prism::embeddings()
        ->using(Provider::VoyageAI, 'voyage-3-lite')
        ->fromInput('The food was delicious and the waiter...')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/voyageai/embeddings-from-input-1.json'), true);
    $embeddings = array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toEqual($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(8);
});

it('returns multiple embeddings from input', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'voyageai/embeddings-from-multiple-inputs');

    $response = Prism::embeddings()
        ->using(Provider::VoyageAI, 'voyage-3-lite')
        ->fromInput('The food was delicious.')
        ->fromInput('The drinks were not so good.')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/voyageai/embeddings-from-multiple-inputs-1.json'), true);
    $embeddings = array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toEqual($embeddings[0]->embedding);
    expect($response->embeddings[1]->embedding)->toEqual($embeddings[1]->embedding);
    expect($response->usage->tokens)->toBe(12);
});
