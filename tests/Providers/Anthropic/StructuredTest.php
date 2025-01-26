<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Structured;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\ValueObjects\Messages\Support\Document;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderRateLimit;
use Illuminate\Support\Carbon;
use Tests\Fixtures\FixtureResponse;

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('messages', 'anthropic/structured');

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
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withSystemPrompt('The tigers game is at 3pm and the temperature will be 70º')
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

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

it('adds rate limit data to the responseMeta', function (): void {
    $requests_reset = Carbon::now()->addSeconds(30);

    FixtureResponse::fakeResponseSequence(
        'messages',
        'anthropic/structured',
        [
            'anthropic-ratelimit-requests-limit' => 1000,
            'anthropic-ratelimit-requests-remaining' => 500,
            'anthropic-ratelimit-requests-reset' => $requests_reset->toISOString(),
            'anthropic-ratelimit-input-tokens-limit' => 80000,
            'anthropic-ratelimit-input-tokens-remaining' => 0,
            'anthropic-ratelimit-input-tokens-reset' => Carbon::now()->addSeconds(60)->toISOString(),
            'anthropic-ratelimit-output-tokens-limit' => 16000,
            'anthropic-ratelimit-output-tokens-remaining' => 15000,
            'anthropic-ratelimit-output-tokens-reset' => Carbon::now()->addSeconds(5)->toISOString(),
            'anthropic-ratelimit-tokens-limit' => 96000,
            'anthropic-ratelimit-tokens-remaining' => 15000,
            'anthropic-ratelimit-tokens-reset' => Carbon::now()->addSeconds(5)->toISOString(),
        ]
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
        ->withSchema($schema)
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withSystemPrompt('The tigers game is at 3pm and the temperature will be 70º')
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    expect($response->responseMeta->rateLimits)->toHaveCount(4);
    expect($response->responseMeta->rateLimits[0])->toBeInstanceOf(ProviderRateLimit::class);
    expect($response->responseMeta->rateLimits[0]->name)->toEqual('requests');
    expect($response->responseMeta->rateLimits[0]->limit)->toEqual(1000);
    expect($response->responseMeta->rateLimits[0]->remaining)->toEqual(500);
    expect($response->responseMeta->rateLimits[0]->resetsAt)->toEqual($requests_reset);
});

it('applies the citations request level providerMeta to all documents', function (): void {
    Prism::fake();

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

    $request = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            (new UserMessage(
                content: 'What color is the grass and sky?',
                additionalContent: [
                    Document::fromText('The grass is green. The sky is blue.'),
                ]
            )),
        ])
        ->withProviderMeta(Provider::Anthropic, ['citations' => true]);

    $payload = Structured::buildHttpRequestPayload($request->toRequest());

    expect($payload['messages'])->toBe([[
        'role' => 'user',
        'content' => [
            [
                'type' => 'text',
                'text' => 'What color is the grass and sky?',
            ],
            [
                'type' => 'document',
                'source' => [
                    'type' => 'text',
                    'media_type' => 'text/plain',
                    'data' => 'The grass is green. The sky is blue.',
                ],
                'citations' => ['enabled' => true],
            ],
        ],
    ]]);
});
