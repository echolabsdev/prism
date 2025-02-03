<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use Tests\Fixtures\FixtureResponse;

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('api/chat', 'ollama/structured');

    $profile = file_get_contents('tests/Fixtures/profile.md');

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('name', 'The users name'),
            new ArraySchema('hobbies', 'a list of the users hobbies',
                new StringSchema('name', 'the name of the hobby'),
            ),
            new ArraySchema('open_source', 'The users open source contributions',
                new StringSchema('name', 'the name of the project'),
            ),
        ],
        ['name', 'hobbies', 'open_source']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Ollama, 'deepseek-r1:14b-qwen-distill-q8_0')
        ->withSystemPrompt('Extract the name, hobbies, and open source projects from the users profile')
        ->withPrompt($profile)
        ->withClientOptions(['timeout' => 10000])
        ->generate();

    expect($response->structured)->toBeArray();
    expect($response->structured)->toHaveKeys([
        'name',
        'hobbies',
        'open_source',
    ]);
    expect($response->structured['name'])->toBeString();
    expect($response->structured['hobbies'])->toBeArray();
    expect($response->structured['open_source'])->toBeArray();

});
