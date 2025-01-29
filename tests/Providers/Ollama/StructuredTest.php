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
    // FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/structured');

    $profile = file_get_contents('profile.md');

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

    dd($response->structured);

    // array:3 [
    //   "name" => "Sarah Chen"
    //   "hobbies" => array:2 [
    //     0 => "rock climbing"
    //     1 => "photography"
    //   ]
    //   "open_source" => array:2 [
    //     0 => "Laravel Telescope - Enhanced Database Query Monitoring"
    //     1 => "Laravel Workflow Manager (Personal Package)"
    // ]
    //   ]

    expect($response->structured)->toBeArray();
    expect($response->structured)->toHaveKeys([
        'weather',
        'game_time',
        'coat_required',
    ]);
    expect($response->structured['weather'])->toBeString();
    expect($response->structured['game_time'])->toBeString();
    expect($response->structured['coat_required'])->toBeBool();

});
