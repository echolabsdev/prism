<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;

beforeEach(function (): void {
    config()->set('prism.providers.openai.api_key', env('OPENAI_API_KEY'));
});

it('returns embeddings from input', function (): void {
    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromInput('The food was delicious and the waiter...')
        ->generate();

    dd($response);
});

it('returns embeddings from file', function (): void {
    $response = Prism::embeddings()
        ->using(Provider::OpenAI, 'text-embedding-ada-002')
        ->fromFile('tests/Fixtures/test-embedding-file.md')
        ->generate();

    dd($response);
});
