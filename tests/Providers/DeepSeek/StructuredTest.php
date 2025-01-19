<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;

beforeEach(function (): void {
    config()->set('prism.providers.deepseek.api_key', env('DEEPSEEK_API_KEY'));
});

it('Throws exception for structured', function (): void {
    $this->expectException(PrismException::class);

    $schema = new ObjectSchema(
        'user',
        'a user object',
        [
            new StringSchema('name', 'The user\'s name'),
        ],
    );

    Prism::structured()
        ->withSchema($schema)
        ->using(Provider::DeepSeek, 'deepseek-chat')
        ->withPrompt('Hi, my name is TJ')
        ->generate();
});
