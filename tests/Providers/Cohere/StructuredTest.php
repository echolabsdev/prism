<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.cohere.api_key', env('COHERE_API_KEY', 'cothere'));
});

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('v2/chat', 'cohere/structured');

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('name', 'The name'),
            new StringSchema('description', 'A description about'),
        ],
        ['name', 'description']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Cohere, 'command-r')
        ->withPrompt('Who are you?')
        ->generate();

    expect($response->structured)->toBeArray()
        ->and($response->structured)->toHaveKeys([
            'name',
            'description'
        ])
        ->and($response->structured['name'])->toBeString()
        ->and($response->structured['description'])->toBeString();
});
