<?php

declare(strict_types=1);

namespace Tests\Unit\Structured;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Structured\Generator;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Usage;

it('generates structured responses', function (): void {
    // Setup
    $fakeProvider = new PrismFake([
        new ProviderResponse(
            text: '{"name": "test", "value": 123}',
            toolCalls: [],
            usage: new Usage(10, 20),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake-1', 'model' => 'fake-model']
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompt: 'test prompt',
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
            response: ['id' => 'fake-2', 'model' => 'fake-model']
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompt: 'test prompt',
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
            response: ['id' => 'fake-3', 'model' => 'fake-model']
        ),
    ]);

    $generator = new Generator($fakeProvider);
    $request = new Request(
        systemPrompt: 'test prompt',
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
            ->and($requests[0]->model)->toBe('test-model')
            ->and($requests[0]->prompt)->toBe('generate data');
    });
});
