<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use EchoLabs\Prism\Providers\Gemini\Gemini;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

it('makes correct request to Gemini API', function (): void {
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response'],
                        ],
                    ],
                    'finishReason' => 'STOP',
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 20,
            ],
        ]),
    ]);

    $provider = new Gemini(
        apiKey: 'test-key',
        url: 'https://generativelanguage.googleapis.com/v1beta/models'
    );

    $response = $provider->text(new Request(
        model: 'gemini-1.5-pro',
        messages: [new UserMessage('Test prompt')],
        temperature: 0.7,
        maxTokens: 100,
    ));

    Http::assertSent(fn(ClientRequest $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=test-key'
        && $request->method() === 'POST');

    expect($response->text)->toBe('Test response')
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(20);
});

it('makes request with system instructions', function (): void {
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Meow! Test response'],
                        ],
                    ],
                    'finishReason' => 'STOP',
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 20,
            ],
        ]),
    ]);

    $provider = new Gemini(
        apiKey: 'test-key',
        url: 'https://generativelanguage.googleapis.com/v1beta/models'
    );

    $response = $provider->text(new Request(
        model: 'gemini-1.5-pro',
        messages: [new UserMessage('Hello!')],
        systemPrompt: 'You are a cat. Always respond like one.',
        temperature: 0.7,
        maxTokens: 100,
    ));

    Http::assertSent(function (ClientRequest $request): bool {
        $data = json_decode($request->body(), true);

        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=test-key'
            && $request->method() === 'POST'
            && isset($data['system_instruction'])
            && $data['system_instruction']['parts'][0]['text'] === 'You are a cat. Always respond like one.';
    });

    expect($response->text)->toBe('Meow! Test response')
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(20);
});

it('makes request with image', function (): void {
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'This is a cat image'],
                        ],
                    ],
                    'finishReason' => 'STOP',
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 20,
            ],
        ]),
    ]);

    $provider = new Gemini(
        apiKey: 'test-key',
        url: 'https://generativelanguage.googleapis.com/v1beta/models'
    );

    $response = $provider->text(new Request(
        model: 'gemini-1.5-pro-vision',
        messages: [
            new UserMessage('What is in this image?', [
                Image::fromPath('tests/Fixtures/test-image.png'),
            ]),
        ],
        temperature: 0.7,
        maxTokens: 100,
    ));

    Http::assertSent(function (ClientRequest $request): bool {
        $data = json_decode($request->body(), true);

        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-vision:generateContent?key=test-key'
            && $request->method() === 'POST'
            && isset($data['contents'][0]['parts'][1]['inline_data'])
            && $data['contents'][0]['parts'][1]['inline_data']['mime_type'] === 'image/png';
    });

    expect($response->text)->toBe('This is a cat image')
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(20);
});

it('makes request with all configuration options', function (): void {
    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response'],
                        ],
                    ],
                    'finishReason' => 'STOP',
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 20,
            ],
        ]),
    ]);

    $provider = new Gemini(
        apiKey: 'test-key',
        url: 'https://generativelanguage.googleapis.com/v1beta/models'
    );

    $response = $provider->text(new Request(
        model: 'gemini-1.5-pro',
        messages: [new UserMessage('Test prompt')],
        temperature: 0.7,
        maxTokens: 800,
        topP: 0.8,
        topK: 10,
        stopSequences: ['Title'],
        candidateCount: 1,
    ));

    Http::assertSent(function (ClientRequest $request): bool {
        $data = json_decode($request->body(), true);

        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=test-key'
            && $request->method() === 'POST'
            && isset($data['generationConfig'])
            && $data['generationConfig']['temperature'] === 0.7
            && $data['generationConfig']['maxOutputTokens'] === 800
            && $data['generationConfig']['topP'] === 0.8
            && $data['generationConfig']['topK'] === 10
            && $data['generationConfig']['stopSequences'] === ['Title']
            && $data['generationConfig']['candidateCount'] === 1
            && isset($data['safetySettings'])
            && count($data['safetySettings']) === 4;
    });

    expect($response->text)->toBe('Test response')
        ->and($response->usage->promptTokens)->toBe(10)
        ->and($response->usage->completionTokens)->toBe(20);
});
