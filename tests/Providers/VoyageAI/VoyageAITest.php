<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use Illuminate\Support\Facades\Http;

it('throws an exception for text', function (): void {
    Http::fake()->preventStrayRequests();

    Prism::text()
        ->using(Provider::VoyageAI, 'test-model')
        ->withPrompt('Hello world.')
        ->generate();
})->throws(PrismException::class, 'EchoLabs\Prism\Providers\VoyageAI\VoyageAI::text is not supported by VoyageAI');

it('throws an exception for structured', function (): void {
    Http::fake()->preventStrayRequests();

    Prism::structured()
        ->using(Provider::VoyageAI, 'test-model')
        ->withSchema(new ObjectSchema('', '', []))
        ->withPrompt('Hello world.')
        ->generate();
})->throws(PrismException::class, 'EchoLabs\Prism\Providers\VoyageAI\VoyageAI::structured is not supported by VoyageAI');
