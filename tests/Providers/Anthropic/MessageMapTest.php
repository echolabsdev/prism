<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Providers\Anthropic\MessageMap;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use InvalidArgumentException;

it('maps user messages', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?'),
        ],
    );

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
        ],
    ]]);
});

it('maps user messages with images from path', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromPath('tests/Fixtures/test-image.png'),
            ]),
        ],
    );

    $mappedMessage = $messageMap();

    expect(data_get($mappedMessage, '0.content.1.type'))
        ->toBe('image');
    expect(data_get($mappedMessage, '0.content.1.source.type'))
        ->toBe('base64');
    expect(data_get($mappedMessage, '0.content.1.source.data'))
        ->toContain(base64_encode(file_get_contents('tests/Fixtures/test-image.png')));
    expect(data_get($mappedMessage, '0.content.1.source.media_type'))
        ->toBe('image/png');
});

it('maps user messages with images from base64', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-image.png')), 'image/png'),
            ]),
        ],
    );

    $mappedMessage = $messageMap();

    expect(data_get($mappedMessage, '0.content.1.type'))
        ->toBe('image');
    expect(data_get($mappedMessage, '0.content.1.source.type'))
        ->toBe('base64');
    expect(data_get($mappedMessage, '0.content.1.source.data'))
        ->toContain(base64_encode(file_get_contents('tests/Fixtures/test-image.png')));
    expect(data_get($mappedMessage, '0.content.1.source.media_type'))
        ->toBe('image/png');
});

it('does not maps user messages with images from url', function (): void {
    $this->expectException(InvalidArgumentException::class);

    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromUrl('https://storage.echolabs.dev/assets/logo.png'),
            ]),
        ],
    );

    $messageMap();
});

it('maps assistant message', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new AssistantMessage('I am Nyx'),
        ],
    );

    expect($messageMap())->toContain([
        'role' => 'assistant',
        'content' => 'I am Nyx',
    ]);
});

it('maps assistant message with tool calls', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new AssistantMessage('I am Nyx', [
                new ToolCall(
                    'tool_1234',
                    'search',
                    [
                        'query' => 'Laravel collection methods',
                    ]
                ),
            ]),
        ],
    );

    expect($messageMap())->toBe([
        [
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I am Nyx',
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'tool_1234',
                    'name' => 'search',
                    'input' => [
                        'query' => 'Laravel collection methods',
                    ],
                ],
            ],
        ],
    ]);
});

it('maps tool result messages', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new ToolResultMessage([
                new ToolResult(
                    'tool_1234',
                    'search',
                    [
                        'query' => 'Laravel collection methods',
                    ],
                    '[search results]'
                ),
            ]),
        ],
    );

    expect($messageMap())->toBe([
        [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'tool_result',
                    'tool_use_id' => 'tool_1234',
                    'content' => '[search results]',
                ],
            ],
        ],
    ]);
});

it('maps system messages', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new SystemMessage('Who are you?'),
        ],
    );

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => 'Who are you?',
    ]]);
});
