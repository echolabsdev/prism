<?php

namespace Tests\Http;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Facades\PrismServer;
use EchoLabs\Prism\Generators\TextGenerator;
use EchoLabs\Prism\Responses\TextResponse;
use EchoLabs\Prism\States\TextState;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\TextResult;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery;

beforeEach(function (): void {
    config()->set('prism.prism_server.enabled', true);
});

it('handles chat requests successfully', function (): void {
    $generator = Mockery::mock(TextGenerator::class);

    $generator->expects('withMessages')
        ->withArgs(fn ($messages): bool => $messages[0] instanceof UserMessage
            && $messages[0]->text() === 'Who are you?')
        ->andReturnSelf();

    $state = new TextState(
        steps: collect([
            new TextResult(
                text: "I'm Nyx!",
                finishReason: FinishReason::Stop,
                toolCalls: [],
                toolResults: [],
                usage: new Usage(10, 10),
                response: ['id' => 'cmp_asdf123', 'model' => 'gpt-4'],
                messages: [
                    new UserMessage('Who are you?'),
                    new AssistantMessage("I'm Nyx!"),
                ]
            ),
        ]),
        responseMessages: collect([
            new AssistantMessage("I'm nyx!"),
        ]),
    );

    $textResponse = new TextResponse($state);

    $generator->expects('__invoke')
        ->andReturns($textResponse);

    PrismServer::register(
        'nyx',
        fn () => $generator
    );

    /** @var TestResponse */
    $response = $this->postJson('prism/openai/v1/chat/completions', [
        'model' => 'nyx',
        'messages' => [[
            'role' => 'user',
            'content' => 'Who are you?',
        ]],
    ]);

    $response->assertOk();

    $now = now();

    Carbon::setTestNow($now);
    expect($response->json())->toBe([
        'id' => 'cmp_asdf123',
        'object' => 'chat.completion',
        'created' => $now->timestamp,
        'model' => 'gpt-4',
        'usage' => [
            'prompt_tokens' => $textResponse->usage->promptTokens,
            'completion_tokens' => $textResponse->usage->completionTokens,
            'total_tokens' => $textResponse->usage->promptTokens
                    + $textResponse->usage->completionTokens,
        ],
        'choices' => [
            [
                'index' => 0,
                'message' => [
                    'content' => "I'm nyx!",
                    'role' => 'assistant',
                ],
                'finish_reason' => 'stop',
            ],
        ],
    ]);
});

it('handles streaming requests', function (): void {
    $generator = Mockery::mock(TextGenerator::class);

    $generator->expects('withMessages')
        ->withArgs(fn ($messages): bool => $messages[0] instanceof UserMessage
            && $messages[0]->text() === 'Who are you?')
        ->andReturnSelf();

    $state = new TextState(
        steps: collect([
            new TextResult(
                text: "I'm Nyx!",
                finishReason: FinishReason::Stop,
                toolCalls: [],
                toolResults: [],
                usage: new Usage(10, 10),
                response: ['id' => 'cmp_asdf123', 'model' => 'gpt-4'],
                messages: [
                    new UserMessage('Who are you?'),
                    new AssistantMessage("I'm Nyx!"),
                ]
            ),
        ]),
        responseMessages: collect([
            new AssistantMessage("I'm nyx!"),
        ]),
    );

    $textResponse = new TextResponse($state);

    $generator->expects('__invoke')
        ->andReturns($textResponse);

    PrismServer::register(
        'nyx',
        fn () => $generator
    );

    /** @var TestResponse */
    $response = $this->postJson('prism/openai/v1/chat/completions', [
        'model' => 'nyx',
        'messages' => [[
            'role' => 'user',
            'content' => 'Who are you?',
        ]],
        'stream' => true,
    ]);

    $streamParts = array_filter(explode("\n", $response->streamedContent()));

    $data = Str::of($streamParts[0])->substr(6);

    $now = now();
    Carbon::setTestNow($now);

    expect(json_decode($data, true))->toBe([
        'id' => $textResponse->response['id'],
        'object' => 'chat.completion.chunk',
        'created' => $now->timestamp,
        'model' => 'gpt-4',
        'choices' => [
            [
                'delta' => [
                    'role' => 'assistant',
                    'content' => "I'm nyx!",
                ],
            ],
        ],
    ]);

    expect((string) Str::of($streamParts[2])->substr(6))->toBe('[DONE]');
});

it('handles invalid model requests', function (): void {
    /** @var TestResponse */
    $response = $this->postJson('prism/openai/v1/chat/completions', [
        'model' => 'nyx',
    ]);

    $response->assertServerError();

    expect($response->json('error.message'))->toContain('nyx');
});

it('handles missing prism', function (): void {
    /** @var TestResponse */
    $response = $this->postJson('prism/openai/v1/chat/completions', [
        'model' => 'nyx',
        'messages' => [[
            'role' => 'user',
            'content' => 'Who are you?',
        ]],
    ]);

    $response->assertServerError();
    expect($response->json('error.message'))
        ->toBe('Prism "nyx" is not registered with PrismServer');
});
