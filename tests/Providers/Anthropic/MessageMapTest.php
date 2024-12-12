<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use InvalidArgumentException;

it('maps user messages', function (): void {
    expect(MessageMap::map([
        new UserMessage('Who are you?'),
    ]))->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
        ],
    ]]);
});

it('filters system messages out when calling map', function (): void {
    expect(MessageMap::map([
        new UserMessage('Who are you?'),
        new SystemMessage('I am Groot.'),
    ]))->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
        ],
    ]]);
});

it('maps user messages with images from path', function (): void {
    $mappedMessage = MessageMap::map([
        new UserMessage('Who are you?', [
            Image::fromPath('tests/Fixtures/test-image.png'),
        ]),
    ]);

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
    $mappedMessage = MessageMap::map([
        new UserMessage('Who are you?', [
            Image::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-image.png')), 'image/png'),
        ]),
    ]);

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
    MessageMap::map([
        new UserMessage('Who are you?', [
            Image::fromUrl('https://storage.echolabs.dev/assets/logo.png'),
        ]),
    ]);
});

it('maps assistant message', function (): void {
    expect(MessageMap::map([
        new AssistantMessage('I am Nyx'),
    ]))->toContain([
        'role' => 'assistant',
        'content' => 'I am Nyx',
    ]);
});

it('maps assistant message with tool calls', function (): void {
    expect(MessageMap::map([
        new AssistantMessage('I am Nyx', [
            new ToolCall(
                'tool_1234',
                'search',
                [
                    'query' => 'Laravel collection methods',
                ]
            ),
        ]),
    ]))->toBe([
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
    expect(MessageMap::map([
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
    ]))->toBe([
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
    expect(MessageMap::mapSystemMessages(
        [new SystemMessage('Who are you?'), new UserMessage('I am rocket.')],
        'I am Thanos. Me first.'
    ))->toBe([
        [
            'type' => 'text',
            'text' => 'I am Thanos. Me first.',
        ],
        [
            'type' => 'text',
            'text' => 'Who are you?',
        ],
    ]);
});
