<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('v1/chat/completions', 'openai/structured-with-multiple-tools-structured-mode');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 90° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::OpenAI, 'gpt-4o')
        ->withTools($tools)
        ->withMaxSteps(4)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    expect($response->structured)->toBeArray();
    expect($response->structured)->toBe([
        'weather' => 'The weather will be 90° and sunny',
        'game_time' => 'The Tigers game is at 3 pm in Detroit',
        'coat_required' => false,
    ]);
});

it('returns structured output using json mode', function (): void {
    FixtureResponse::fakeResponseSequence('v1/chat/completions', 'openai/structured-with-multiple-tools-json-mode');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 90° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::OpenAI, 'gpt-4-turbo')
        ->withTools($tools)
        ->withMaxSteps(4)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    expect($response->structured)->toBeArray();
    expect($response->structured)->toBe([
        'weather' => 'The weather will be 90° and sunny',
        'game_time' => 'The tigers game is at 3pm in Detroit',
        'coat_required' => false,
    ]);
});

it('schema strict defaults to null', function (): void {
    FixtureResponse::fakeResponseSequence('v1/chat/completions', 'openai/strict-schema-defaults');

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
    );

    $response = Prism::structured()
        ->using(Provider::OpenAI, 'gpt-4o')
        ->withSchema($schema)
        ->withSystemPrompt('The game time is 3pm and the weather is 80° and sunny')
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    Http::assertSent(function (Request $request): true {
        $body = json_decode($request->body(), true);

        expect(array_keys(data_get($body, 'response_format.json_schema')))->not->toContain('strict');

        return true;
    });
});

it('uses meta to define strict mode', function (): void {
    FixtureResponse::fakeResponseSequence(
        'v1/chat/completions',
        'openai/strict-schema-setting-set'
    );

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->using(Provider::OpenAI, 'gpt-4o')
        ->withSchema($schema)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->withProviderMeta(Provider::OpenAI, [
            'schema' => ['strict' => true],
        ])
        ->generate();

    Http::assertSent(function (Request $request): true {
        $body = json_decode($request->body(), true);

        expect(data_get($body, 'response_format.json_schema.strict'))->toBeTrue();

        return true;
    });
});

it('throws an exception when there is a refusal', function (): void {
    $this->expectException(PrismException::class);
    $this->expectExceptionMessage('OpenAI Refusal: Could not process your request');

    Http::fake([
        'v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'refusal' => 'Could not process your request',
                ],
            ]],
        ]),
    ]);

    Http::preventStrayRequests();

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    Prism::structured()
        ->using(Provider::OpenAI, 'gpt-4o')
        ->withSchema($schema)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    Http::assertSent(function (Request $request): true {
        $body = json_decode($request->body(), true);

        expect(data_get($body, 'response_format.json_schema.strict'))->toBeTrue();

        return true;
    });
});
