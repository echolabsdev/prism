<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\Schema\ObjectSchema;

it('throws an exception for text', function (): void {
    Http::fake()->preventStrayRequests();

    Prism::text()
        ->using(Provider::VoyageAI, 'test-model')
        ->withPrompt('Hello world.')
        ->generate();
})->throws(PrismException::class, 'PrismPHP\Prism\Providers\VoyageAI\VoyageAI::text is not supported by VoyageAI');

it('throws an exception for structured', function (): void {
    Http::fake()->preventStrayRequests();

    Prism::structured()
        ->using(Provider::VoyageAI, 'test-model')
        ->withSchema(new ObjectSchema('', '', []))
        ->withPrompt('Hello world.')
        ->generate();
})->throws(PrismException::class, 'PrismPHP\Prism\Providers\VoyageAI\VoyageAI::structured is not supported by VoyageAI');
