<?php

namespace Tests\Http;

use EchoLabs\Prism\Drivers\OpenAI\Client;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('prism.providers.openai.api_key', env('OPENAI_API_KEY'));
    config()->set('prism.providers.openai.organization', env('OPENAI_ORGANIZATION'));

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1406849400,
            'model' => 'gpt-4o',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello, how can I assist you today?',
                    ],
                    'finish_reason' => 'stop',
                    'index' => 0,
                ],
            ],
            'usage' => [
                'prompt_tokens' => 5,
                'completion_tokens' => 7,
                'total_tokens' => 12,
            ],
        ], 200),
    ]);
});

it('handles addition of organization for OpenAI', function (): void {
    $this->client = new Client(
        apiKey: 'test-api-key',
        url: 'https://api.openai.com/v1',
        organization: 'test-organization',
    );

    $this->client->messages(
        model: 'gpt-4',
        messages: [
            ['role' => 'user', 'content' => 'Hello!'],
        ],
        maxTokens: 50,
        temperature: 0.7,
        topP: 1.0,
        tools: null
    );

    Http::assertSent(fn (Request $request) => $request->hasHeader('OpenAI-Organization', 'test-organization'));
});

it('handles not having an organization for OpenAI', function (): void {
    $this->client = new Client(
        apiKey: 'test-api-key',
        url: 'https://api.openai.com/v1'
    );

    $this->client->messages(
        model: 'gpt-4',
        messages: [
            ['role' => 'user', 'content' => 'Hello!'],
        ],
        maxTokens: 50,
        temperature: 0.7,
        topP: 1.0,
        tools: null
    );

    Http::assertNotSent(fn (Request $request) => $request->hasHeader('OpenAI-Organization', 'test-organization'));
});
