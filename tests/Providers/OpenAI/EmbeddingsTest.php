<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
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

    expect($response->embeddings[0])->toBeArray();
    expect($response->embeddings[0])->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(8);
});

it('returns embeddings from array input', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'openai/embeddings-array-input');

    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromInput([
            'The food was delicious and the waiter...',
            'The food was delicious and the waiter...',
        ])
        ->generate();

    expect($response->embeddings[1])->toBeArray();
    expect($response->embeddings[1])->not->toBeEmpty();
    expect($response->embeddings[0])->toEqual($response->embeddings[1]);
    expect($response->usage->tokens)->toBe(16);
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('v1/embeddings', 'openai/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings)->not->toBeEmpty();
    expect($response->usage->tokens)->toBe(1378);
});
