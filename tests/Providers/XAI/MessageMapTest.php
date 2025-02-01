<?php

declare(strict_types=1);

namespace Tests\Providers\XAI;

use EchoLabs\Prism\Providers\XAI\Maps\MessageMap;
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

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
        ],
    ]]);
});

it('maps assistant message', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new AssistantMessage('I am Nyx'),
        ]
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
        ]
    );

    expect($messageMap())->toBe([[
        'role' => 'assistant',
        'content' => 'I am Nyx',
        'tool_calls' => [[
            'id' => 'tool_1234',
            'type' => 'function',
            'function' => [
                'name' => 'search',
                'arguments' => json_encode([
                    'query' => 'Laravel collection methods',
                ]),
            ],
        ]],
    ]]);
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

    expect($messageMap())->toBe([[
        'role' => 'tool',
        'tool_call_id' => 'tool_1234',
        'content' => '[search results]',
    ]]);
});

it('maps system prompt', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new SystemMessage('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]'),
            new UserMessage('Who are you?'),
        ]
    );

    expect($messageMap())->toBe([
        [
            'role' => 'system',
            'content' => 'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]',
        ],
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Who are you?'],
            ],
        ],
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

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/png;base64,'.base64_encode(file_get_contents('tests/Fixtures/test-image.png')),
                ],
            ],
        ],
    ]]);
});

it('maps user messages with images from base64', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-image.png')), 'image/png'),
            ]),
        ]
    );

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/png;base64,'.base64_encode(file_get_contents('tests/Fixtures/test-image.png')),
                ],
            ],
        ],
    ]]);
});

it('maps user messages with images from url', function (): void {
    $messageMap = new MessageMap(
        messages: [
            new UserMessage('Who are you?', [
                Image::fromUrl('https://storage.echolabs.dev/assets/logo.png'),
            ]),
        ]
    );

    expect($messageMap())->toBe([[
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => 'Who are you?'],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'https://storage.echolabs.dev/assets/logo.png',
                ],
            ],
        ],
    ]]);
});
