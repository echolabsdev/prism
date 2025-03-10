<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\Providers\Gemini\Concerns\ValidatesResponse;

arch()->expect([
    'Providers\Gemini\Handlers\Text',
    'Providers\Gemini\Handlers\Structured',
])
    ->toUseTrait(ValidatesResponse::class);

it('throws a PrismRateLimitedException with a 429 response code for text and structured', function (): void {
    Http::fake([
        '*' => Http::response(
            status: 429,
        ),
    ])->preventStrayRequests();

    Prism::text()
        ->using(Provider::Gemini, 'fake-model')
        ->withPrompt('Hello world!')
        ->generate();

})->throws(PrismRateLimitedException::class);

it('throws a PrismRateLimitedException with a 429 response code for emebddings', function (): void {
    Http::fake([
        '*' => Http::response(
            status: 429,
        ),
    ])->preventStrayRequests();

    Prism::embeddings()
        ->using(Provider::Gemini, 'fake-model')
        ->fromInput('Hello world!')
        ->generate();

})->throws(PrismRateLimitedException::class);
