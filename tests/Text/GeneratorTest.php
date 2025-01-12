<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\PendingRequest;
use EchoLabs\Prism\Text\Response;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Tests\TestDoubles\TestProvider;

beforeEach(function (): void {
    $this->provider = $provider = new TestProvider;

    resolve('prism-manager')->extend('test-provider', fn($config): \Tests\TestDoubles\TestProvider => $provider);
});

test('it can generate basic text response', function (): void {
    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withPrompt('Hello');

    $response = $request->generate();

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->text)
        ->toBe("I'm nyx!")
        ->and($this->provider->callCount)
        ->toBe(1);
});

test('it can handle tool calls', function (): void {
    $tool = Tool::as('test-tool')
        ->for('A test tool')
        ->withStringParameter('input', 'Test input')
        ->using(fn (string $input): string => "Tool response: {$input}");

    $toolCall = new ToolCall(
        id: '123',
        name: 'test-tool',
        arguments: ['input' => 'test'],
    );

    $this->provider->withResponse(
        new ProviderResponse(
            text: 'Testing tools',
            toolCalls: [$toolCall],
            usage: new Usage(10, 10),
            finishReason: FinishReason::ToolCalls,
            response: ['id' => '123', 'model' => 'test-model'],
        )
    );

    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withTools([$tool])
        ->withPrompt('Use the test tool');

    $response = $request->generate();

    expect($response->toolCalls)
        ->toHaveCount(1)
        ->and($response->toolResults)
        ->toHaveCount(1)
        ->and($response->toolResults[0]->result)
        ->toBe('Tool response: test');
});

test('it continues generating until max steps or stop', function (): void {
    // First response triggers tool call
    $toolCall = new ToolCall(
        id: '123',
        name: 'test-tool',
        arguments: ['input' => 'test'],
    );

    $this->provider->withResponseChain([
        new ProviderResponse(
            text: 'First response',
            toolCalls: [$toolCall],
            usage: new Usage(10, 10),
            finishReason: FinishReason::ToolCalls,
            response: ['id' => '123', 'model' => 'test-model'],
        ),
        new ProviderResponse(
            text: 'Final response',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            response: ['id' => '123', 'model' => 'test-model'],
        ),
    ]);

    $tool = Tool::as('test-tool')
        ->for('A test tool')
        ->withStringParameter('input', 'Test input')
        ->using(fn (string $input): string => "Tool response: {$input}");

    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withTools([$tool])
        ->withMaxSteps(2)
        ->withPrompt('Use the test tool multiple times');

    $response = $request->generate();

    expect($this->provider->callCount)
        ->toBe(2)
        ->and($response->text)
        ->toBe('Final response')
        ->and($response->steps)
        ->toHaveCount(2);
});

test('it accumulates response messages', function (): void {
    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withPrompt('Hello');

    $response = $request->generate();

    expect($response->responseMessages)
        ->toHaveCount(1)
        ->and($response->responseMessages->first())
        ->toBeInstanceOf(AssistantMessage::class);
});

test('it stops at max steps even without stop finish reason', function (): void {
    $this->provider->withResponseChain([
        new ProviderResponse(
            text: 'Response 1',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Length,
            response: ['id' => '123', 'model' => 'test-model'],
        ),
        new ProviderResponse(
            text: 'Response 2',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Length,
            response: ['id' => '123', 'model' => 'test-model'],
        ),
    ]);

    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withMaxSteps(2)
        ->withPrompt('Generate multiple responses');

    $response = $request->generate();

    expect($this->provider->callCount)
        ->toBe(2)
        ->and($response->steps)
        ->toHaveCount(2)
        ->and($response->text)
        ->toBe('Response 2');
});

test('it throws when using both prompt and messages', function (): void {
    $pendingRequest = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withPrompt('Hello')
        ->withMessages([
            new \EchoLabs\Prism\ValueObjects\Messages\UserMessage('Test message'),
        ]);

    expect(fn (): \EchoLabs\Prism\Text\Request => $pendingRequest->toRequest())
        ->toThrow(\EchoLabs\Prism\Exceptions\PrismException::class, 'You can only use `prompt` or `messages`');
});
