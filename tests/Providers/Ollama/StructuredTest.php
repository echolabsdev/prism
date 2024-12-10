<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use Tests\Fixtures\FixtureResponse;

it('returns structured output from prompts', function (): void {
    FixtureResponse::fakeResponseSequence(
        'v1/chat/completions',
        'ollama/structured-with-prompts-structured-mode'
    );

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast, including temperature (if provided)'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required based on the weather conditions'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Ollama, 'qwen2.5:14b')
        ->usingTemperature(0)
        ->withSystemPrompt('The tigers game is at 3pm and the expected weather is 90° and sunny')
        ->withPrompt('What time is the tigers game today in detroit and should I wear a coat?')
        ->generate();

    expect($response->object)->toBeArray();
    expect($response->object)->toMatchArray([
        'weather' => '90° and sunny',
        'game_time' => '3pm',
        'coat_required' => false,
    ]);
});

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence(
        'v1/chat/completions',
        'ollama/structured-with-multiple-tools-structured-mode'
    );

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 90° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm'),
    ];

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast, including temperature (if provided)'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required based on the weather conditions'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Ollama, 'qwen2.5:14b')
        ->withTools($tools)
        ->usingTemperature(0)
        ->withMaxSteps(4)
        ->withPrompt('What time is the tigers game today in detroit and should I wear a coat?')
        ->generate();

    expect($response->object)->toBeArray();
    expect($response->object)->toMatchArray([
        'weather' => '90° and sunny',
        'game_time' => '3pm',
        'coat_required' => false,
    ]);
});
