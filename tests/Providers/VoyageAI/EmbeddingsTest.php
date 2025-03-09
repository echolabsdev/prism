<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\ValueObjects\Embedding;
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

it('returns embeddings with inputType set', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'voyageai/embeddings-with-input-type');

    $response = Prism::embeddings()
        ->using(Provider::VoyageAI, 'voyage-3-lite')
        ->fromInput('The food was delicious and the waiter...')
        ->withProviderMeta(Provider::VoyageAI, ['inputType' => 'query'])
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/voyageai/embeddings-with-input-type-1.json'), true);
    $embeddings = array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toEqual($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(7);
});

it('returns embeddings with truncation set', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'voyageai/embeddings-with-truncation');

    $response = Prism::embeddings()
        ->using(Provider::VoyageAI, 'voyage-3-lite')
        ->fromInput('The food was delicious and the waiter...')
        ->withProviderMeta(Provider::VoyageAI, ['truncation' => false])
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/voyageai/embeddings-with-truncation-1.json'), true);
    $embeddings = array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toEqual($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(8);
});

it('returns embeddings with inputType and truncation', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'voyageai/embeddings-with-input-type-and-truncation');

    $response = Prism::embeddings()
        ->using(Provider::VoyageAI, 'voyage-3-lite')
        ->fromInput('The food was delicious and the waiter...')
        ->withProviderMeta(Provider::VoyageAI, [
            'inputType' => 'query',
            'truncation' => false,
        ])
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/voyageai/embeddings-with-input-type-and-truncation-1.json'), true);
    $embeddings = array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($embeddings, 'data'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toEqual($embeddings[0]->embedding);
    expect($response->usage->tokens)->toBe(7);
});

it('throws a PrismRateLimitedException for a 429 response code', function (): void {
    Http::fake([
        '*' => Http::response(
            status: 429,
        ),
    ])->preventStrayRequests();

    Prism::embeddings()
        ->using(Provider::VoyageAI, 'fake-model')
        ->fromInput('Hello world!')
        ->generate();

})->throws(PrismRateLimitedException::class);
