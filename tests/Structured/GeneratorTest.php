<?php

declare(strict_types=1);

namespace Tests\Unit\Structured;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\Structured\Generator;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response;
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;

it('generates structured responses', function (): void {
    // Setup
    $fakeProvider = new PrismFake([
        new ProviderResponse(
            text: '{"name": "test", "value": 123}',
            toolCalls: [],
            usage: new Usage(10, 20),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompts: [],
        model: 'test-model',
        prompt: 'generate data',
        messages: [],
        maxTokens: null,
        temperature: null,
        topP: null,
        clientOptions: [],
        clientRetry: [0],
        schema: new class implements \EchoLabs\Prism\Contracts\Schema
        {
            public function name(): string
            {
                return 'test';
            }

            public function toArray(): array
            {
                return [];
            }
        },
        providerMeta: [],
        mode: \EchoLabs\Prism\Enums\StructuredMode::Json,
    );

    // Execute
    $response = $generator->generate($request);

    // Verify basic response structure
    expect($response->text)->toBe('{"name": "test", "value": 123}')
        ->and($response->structured)->toBe(['name' => 'test', 'value' => 123])
        ->and($response->finishReason)->toBe(FinishReason::Stop)
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(20);

    // Verify response messages are tracked
    expect($response->responseMessages->first())
        ->toBeInstanceOf(AssistantMessage::class)
        ->and($response->responseMessages->first()->content)
        ->toBe('{"name": "test", "value": 123}');
});

it('handles invalid JSON responses', function (): void {
    // Setup with invalid JSON response
    $fakeProvider = new PrismFake([
        new ProviderResponse(
            text: 'invalid json response',
            toolCalls: [],
            usage: new Usage(5, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompts: [],
        model: 'test-model',
        prompt: 'generate data',
        messages: [],
        maxTokens: null,
        temperature: null,
        topP: null,
        clientOptions: [],
        clientRetry: [0],
        schema: new class implements \EchoLabs\Prism\Contracts\Schema
        {
            public function name(): string
            {
                return 'test';
            }

            public function toArray(): array
            {
                return [];
            }
        },
        providerMeta: [],
        mode: \EchoLabs\Prism\Enums\StructuredMode::Json,
    );

    // Expect an exception for invalid JSON in structured mode
    expect(fn (): \EchoLabs\Prism\Structured\Response => $generator->generate($request))
        ->toThrow(PrismException::class, 'Structured object could not be decoded. Received: invalid json response');
});

it('tracks provider responses properly', function (): void {
    // Setup
    $fakeProvider = new PrismFake([
        new ProviderResponse(
            text: '{"result": "success"}',
            toolCalls: [],
            usage: new Usage(5, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompts: [],
        model: 'test-model',
        prompt: 'generate data',
        messages: [],
        maxTokens: null,
        temperature: null,
        topP: null,
        clientOptions: [],
        clientRetry: [0],
        schema: new class implements \EchoLabs\Prism\Contracts\Schema
        {
            public function name(): string
            {
                return 'test';
            }

            public function toArray(): array
            {
                return [];
            }
        },
        providerMeta: [],
        mode: \EchoLabs\Prism\Enums\StructuredMode::Json,
    );

    // Execute
    $response = $generator->generate($request);

    // Verify provider interaction tracking
    $fakeProvider->assertCallCount(1);
    $fakeProvider->assertRequest(function (array $requests): void {
        expect($requests[0])->toBeInstanceOf(Request::class)
            ->and($requests[0]->model())->toBe('test-model')
            ->and($requests[0]->prompt())->toBe('generate data');
    });
});

test('it adds user message and assistant message response to first step', function (): void {
    Prism::fake([
        new ProviderResponse(
            text: json_encode(['I am a string']),
            toolCalls: [],
            usage: new Usage(5, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        ),
    ]);

    $request = Prism::structured()
        ->using(Provider::Anthropic, 'test')
        ->withSchema(new ArraySchema('test schema', 'testing', new StringSchema('stringy', 'string')))
        ->withSystemPrompt('System Prompt')
        ->withPrompt('User Prompt');

    $response = $request->generate();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->steps[0]->messages)->toHaveCount(2);

    /** @var UserMessage */
    $user_message = $response->steps[0]->messages[0];

    expect($user_message)->toBeInstanceOf(UserMessage::class)
        ->and($user_message->text())
        ->toBe('User Prompt');

    /** @var AssistantMessage */
    $assistant_message = $response->steps[0]->messages[1];

    expect($assistant_message)->toBeInstanceOf(AssistantMessage::class)
        ->and($assistant_message->content)
        ->toBe(json_encode(['I am a string']));
});

test('it adds system prompts first step', function (): void {
    Prism::fake([
        new ProviderResponse(
            text: json_encode(['I am a string']),
            toolCalls: [],
            usage: new Usage(5, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        ),
    ]);

    $request = Prism::structured()
        ->using(Provider::Anthropic, 'test')
        ->withSchema(new ArraySchema('test schema', 'testing', new StringSchema('stringy', 'string')))
        ->withSystemPrompts([
            new SystemMessage('System Prompt 1'),
            new SystemMessage('System Prompt 2'),
        ])
        ->withPrompt('User Prompt');

    $response = $request->generate();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->steps[0]->systemPrompts)->toHaveCount(2);

    /** @var SystemMessage */
    $messageOne = $response->steps[0]->systemPrompts[0];

    expect($messageOne)->toBeInstanceOf(SystemMessage::class)
        ->and($messageOne->content)
        ->toBe('System Prompt 1');

    /** @var SystemMessage */
    $messageTwo = $response->steps[0]->systemPrompts[1];

    expect($messageTwo)->toBeInstanceOf(SystemMessage::class)
        ->and($messageTwo->content)
        ->toBe('System Prompt 2');
});
