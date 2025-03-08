<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\ValueObjects\Embedding;
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

    $embeddings = json_decode(file_get_contents('tests/Fixtures/gemini/embeddings-input-1.json'), true);
    $embedding = Embedding::fromArray(data_get($embeddings, 'embedding.values'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embedding->embedding);
    expect($response->usage->tokens)->toBe(0); // Gemini doesn't provide token usage
});

it('returns embeddings from file', function (): void {
    FixtureResponse::fakeResponseSequence('models/text-embedding-004:embedContent', 'gemini/embeddings-file');

    $response = Prism::embeddings()
        ->using(Provider::Gemini, 'text-embedding-004')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    $embeddings = json_decode(file_get_contents('tests/Fixtures/gemini/embeddings-file-1.json'), true);
    $embedding = Embedding::fromArray(data_get($embeddings, 'embedding.values'));

    expect($response->embeddings)->toBeArray();
    expect($response->embeddings[0]->embedding)->toBe($embedding->embedding);
    expect($response->usage->tokens)->toBe(0); // Gemini doesn't provide token usage
});

it('throws an exception with multiple inputs', function (): void {
    Http::preventStrayRequests();

    $response = Prism::embeddings()
        ->using(Provider::Gemini, 'text-embedding-004')
        ->fromInput('1')
        ->fromInput('2')
        ->generate();
})->throws(PrismException::class, 'Gemini Error: Prism currently only supports one input at a time with Gemini.');
