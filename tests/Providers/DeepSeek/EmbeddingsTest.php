<?php

declare(strict_types=1);

use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Prism;

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
