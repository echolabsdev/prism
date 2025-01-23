<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Text\PendingRequest;
use EchoLabs\Prism\Text\Response;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Tests\TestDoubles\TestProvider;

beforeEach(function (): void {
    $this->provider = $provider = new TestProvider;

    resolve('prism-manager')->extend('test-provider', fn ($config): \Tests\TestDoubles\TestProvider => $provider);
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
            responseMeta: new ResponseMeta('123', 'test-model'),
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
            responseMeta: new ResponseMeta('123', 'test-model'),
        ),
        new ProviderResponse(
            text: 'Final response',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'test-model'),
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

test('it continues generating until max steps', function (): void {
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
            responseMeta: new ResponseMeta('123', 'test-model'),
        ),
        new ProviderResponse(
            text: 'Final response',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'test-model'),
        ),
    ]);

    $tool = Tool::as('test-tool')
        ->for('A test tool')
        ->withStringParameter('input', 'Test input')
        ->using(fn (string $input): string => "Tool response: {$input}");

    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withTools([$tool])
        ->withMaxSteps(1)
        ->withPrompt('Use the test tool multiple times');

    $response = $request->generate();

    expect($this->provider->callCount)
        ->toBe(1)
        ->and($response->text)
        ->toBe('First response')
        ->and($response->steps)
        ->toHaveCount(1);
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
            responseMeta: new ResponseMeta('123', 'test-model'),
        ),
        new ProviderResponse(
            text: 'Response 2',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Length,
            responseMeta: new ResponseMeta('123', 'test-model'),
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

test('it correctly builds message chain with tools', function (): void {
    // First response triggers tool call
    $toolCall = new ToolCall(
        id: '123',
        name: 'test-tool',
        arguments: ['input' => 'test'],
    );

    $this->provider->withResponseChain([
        // First response with tool call
        new ProviderResponse(
            text: 'First response',
            toolCalls: [$toolCall],
            usage: new Usage(10, 10),
            finishReason: FinishReason::ToolCalls,
            responseMeta: new ResponseMeta('123', 'test-model'),
        ),
        // Second response that continues the conversation
        new ProviderResponse(
            text: 'Final response',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'test-model'),
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
        ->withPrompt('Use the test tool and continue the conversation');

    $response = $request->generate();

    // Test that we get both responses in the steps
    expect($response->steps)
        ->toHaveCount(2)
        ->and($response->steps[0]->text)
        ->toBe('First response')
        ->and($response->steps[1]->text)
        ->toBe('Final response');

    // Test the complete message chain
    // Test response messages
    expect($response->responseMessages)->toHaveCount(2);

    /** @var AssistantMessage */
    $firstMessage = $response->responseMessages[0];
    expect($firstMessage)
        ->toBeInstanceOf(AssistantMessage::class)
        ->and($firstMessage->content)->toBe('First response')
        ->and($firstMessage->toolCalls)->toHaveCount(1)
        ->and($firstMessage->toolCalls[0]->name)->toBe('test-tool');

    /** @var AssistantMessage */
    $secondMessage = $response->responseMessages[1];
    expect($secondMessage)
        ->toBeInstanceOf(AssistantMessage::class)
        ->and($secondMessage->content)->toBe('Final response')
        ->and($secondMessage->toolCalls)->toBeEmpty();

    // Test that tool results were captured
    expect($response->steps[0]->toolResults)
        ->toHaveCount(1)
        ->and($response->steps[0]->toolResults[0]->result)
        ->toBe('Tool response: test');

    expect($response->messages->toArray())
        ->toHaveCount(4)
        ->sequence(
            fn ($message) => $message->toBeInstanceOf(UserMessage::class),
            fn ($message) => $message->toBeInstanceOf(AssistantMessage::class),
            fn ($message) => $message->toBeInstanceOf(ToolResultMessage::class),
            fn ($message) => $message->toBeInstanceOf(AssistantMessage::class),
        );
});

test('it adds the system message and user message to first step', function (): void {
    $request = (new PendingRequest)
        ->using('test-provider', 'test-model')
        ->withSystemPrompt('System Prompt')
        ->withPrompt('User Prompt');

    $response = $request->generate();

    expect($response)->toBeInstanceOf(Response::class);

    /** @var SystemMessage */
    $system_message = $response->steps[0]->messages[0];

    expect($system_message)->toBeInstanceOf(SystemMessage::class)
        ->and($system_message->content)
        ->toBe('System Prompt');

    /** @var UserMessage */
    $user_message = $response->steps[0]->messages[1];

    expect($user_message)->toBeInstanceOf(UserMessage::class)
        ->and($user_message->text())
        ->toBe('User Prompt');
});
