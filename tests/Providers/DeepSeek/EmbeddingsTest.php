<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Prism;

beforeEach(function (): void {
    config()->set('prism.providers.deepseek.api_key', env('DEEPSEEK_API_KEY'));
});

it('Throws exception for embeddings', function (): void {
    $this->expectException(PrismException::class);

    Prism::embeddings()
        ->using(Provider::DeepSeek, 'deepseek-chat')
        ->fromInput('Hello, how are you?')
        ->generate();
});
