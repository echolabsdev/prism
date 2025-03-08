<?php

declare(strict_types=1);

use PrismPHP\Prism\Providers\Ollama\Maps\MessageMap;
use PrismPHP\Prism\ValueObjects\Messages\AssistantMessage;
use PrismPHP\Prism\ValueObjects\Messages\Support\Image;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;
use PrismPHP\Prism\ValueObjects\Messages\ToolResultMessage;
use PrismPHP\Prism\ValueObjects\Messages\UserMessage;
use PrismPHP\Prism\ValueObjects\ToolResult;

it('maps system messages correctly', function (): void {
    $systemMessage = new SystemMessage('System instruction');

    $messageMap = new MessageMap([$systemMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'system',
            'content' => 'System instruction',
        ],
    ]);
});

it('maps user messages correctly', function (): void {
    $userMessage = new UserMessage('User input');

    $messageMap = new MessageMap([$userMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'user',
            'content' => 'User input',
        ],
    ]);
});

it('maps user messages with images correctly', function (): void {
    $image = new Image('base64data', 'image/jpeg');
    $userMessage = new UserMessage('User input with image', [$image]);

    $messageMap = new MessageMap([$userMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'user',
            'content' => 'User input with image',
            'images' => ['base64data'],
        ],
    ]);
});

it('maps assistant messages correctly', function (): void {
    $assistantMessage = new AssistantMessage('Assistant response');

    $messageMap = new MessageMap([$assistantMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'assistant',
            'content' => 'Assistant response',
        ],
    ]);
});

it('maps tool result messages correctly', function (): void {
    $toolResult = new ToolResult(
        toolCallId: 'tool-1',
        toolName: 'test-tool',
        args: ['query' => 'test'],
        result: 'Tool execution result'
    );
    $toolResultMessage = new ToolResultMessage([$toolResult]);

    $messageMap = new MessageMap([$toolResultMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'tool',
            'content' => 'Tool execution result',
        ],
    ]);
});

it('maps tool result messages with non-string results correctly', function (): void {
    $toolResult = new ToolResult(
        toolCallId: 'tool-1',
        toolName: 'test-tool',
        args: ['query' => 'test'],
        result: ['key' => 'value']
    );
    $toolResultMessage = new ToolResultMessage([$toolResult]);

    $messageMap = new MessageMap([$toolResultMessage]);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'tool',
            'content' => '{"key":"value"}',
        ],
    ]);
});

it('maps multiple messages in sequence correctly', function (): void {
    $messages = [
        new SystemMessage('System instruction'),
        new UserMessage('User input'),
        new AssistantMessage('Assistant response'),
    ];

    $messageMap = new MessageMap($messages);
    $result = $messageMap->map();

    expect($result)->toBe([
        [
            'role' => 'system',
            'content' => 'System instruction',
        ],
        [
            'role' => 'user',
            'content' => 'User input',
        ],
        [
            'role' => 'assistant',
            'content' => 'Assistant response',
        ],
    ]);
});

it('throws exception for unknown message type', function (): void {
    $invalidMessage = new class implements \PrismPHP\Prism\Contracts\Message {};
    $messageMap = new MessageMap([$invalidMessage]);

    expect(fn (): array => $messageMap->map())
        ->toThrow(Exception::class, 'Could not map message type '.$invalidMessage::class);
});
