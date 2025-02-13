<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;

it('maps user messages', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?'),
        ]
    );

    expect($messageMap())->toBe([
        'contents' => [[
            'role' => 'user',
            'parts' => [
                ['text' => 'Who are you?'],
            ],
        ]],
    ]);
});

it('maps user messages with images from path', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromPath('tests/Fixtures/test-image.png'),
            ]),
        ]
    );

    $mappedMessage = $messageMap();

    expect(data_get($mappedMessage, 'contents.0.parts.1.inline_data.mime_type'))
        ->toBe('image/png');
    expect(data_get($mappedMessage, 'contents.0.parts.1.inline_data.data'))
        ->toBe(base64_encode(file_get_contents('tests/Fixtures/test-image.png')));
});

it('maps user messages with images from base64', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-image.png')), 'image/png'),
            ]),
        ]
    );

    $mappedMessage = $messageMap();

    expect(data_get($mappedMessage, 'contents.0.parts.1.inline_data.mime_type'))
        ->toBe('image/png');
    expect(data_get($mappedMessage, 'contents.0.parts.1.inline_data.data'))
        ->toBe(base64_encode(file_get_contents('tests/Fixtures/test-image.png')));
});

it('maps assistant message', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new AssistantMessage('I am Nyx'),
        ]
    );

    expect($messageMap())->toBe([
        'contents' => [[
            'role' => 'model',
            'parts' => [
                ['text' => 'I am Nyx'],
            ],
        ]],
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
        ]
    );

    expect($messageMap())->toBe([
        'contents' => [[
            'role' => 'model',
            'parts' => [
                ['text' => 'I am Nyx'],
                [
                    'functionCall' => [
                        'name' => 'search',
                        'args' => [
                            'query' => 'Laravel collection methods',
                        ],
                    ],
                ],
            ],
        ]],
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
        ]
    );

    expect($messageMap())->toBe([
        'contents' => [[
            'role' => 'user',
            'parts' => [
                [
                    'functionResponse' => [
                        'name' => 'search',
                        'response' => [
                            'name' => 'search',
                            'content' => '"[search results]"',
                        ],
                    ],
                ],
            ],
        ]],
    ]);
});

it('maps system prompt', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new SystemMessage('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]'),
            new UserMessage('Who are you?'),
        ]
    );

    expect($messageMap())->toBe([
        'contents' => [[
            'role' => 'user',
            'parts' => [
                ['text' => 'Who are you?'],
            ],
        ]],
        'system_instruction' => [
            'parts' => [
                ['text' => 'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]'],
            ],
        ],
    ]);
});
