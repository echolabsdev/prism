<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\Structured\PendingRequest;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use Illuminate\Support\Facades\App;

beforeEach(function (): void {
    $this->pendingRequest = new PendingRequest;
});

test('it requires a schema', function (): void {
    $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withPrompt('Test prompt');

    expect(fn () => $this->pendingRequest->toRequest())
        ->toThrow(PrismException::class, 'A schema is required for structured output');
});

test('it cannot have both prompt and messages', function (): void {
    $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withSchema(new StringSchema('test', 'test description'))
        ->withPrompt('Test prompt')
        ->withMessages([new UserMessage('Test message')]);

    expect(fn () => $this->pendingRequest->toRequest())
        ->toThrow(PrismException::class, 'You can only use `prompt` or `messages`');
});

test('it converts prompt to message', function (): void {
    $prompt = 'Test prompt';

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withSchema(new StringSchema('test', 'test description'))
        ->withPrompt($prompt)
        ->toRequest();

    expect($request->messages)
        ->toHaveCount(1)
        ->and($request->messages[0])->toBeInstanceOf(UserMessage::class)
        ->and($request->messages[0]->text())->toBe($prompt);
});

test('it generates a proper request object', function (): void {
    $schema = new StringSchema('test', 'test description');
    $model = 'gpt-4';
    $prompt = 'Test prompt';
    $systemPrompt = 'System prompt';
    $temperature = 0.7;
    $maxTokens = 100;
    $topP = 0.9;
    $clientOptions = ['timeout' => 30];
    $clientRetry = [3, 100, null, true];
    $providerMeta = ['test' => 'meta'];

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, $model)
        ->withSchema($schema)
        ->withPrompt($prompt)
        ->withSystemPrompt($systemPrompt)
        ->usingTemperature($temperature)
        ->withMaxTokens($maxTokens)
        ->usingTopP($topP)
        ->withClientOptions($clientOptions)
        ->withClientRetry(...$clientRetry)
        ->withProviderMeta(Provider::OpenAI, $providerMeta)
        ->toRequest();

    expect($request)
        ->toBeInstanceOf(Request::class)
        ->model->toBe($model)
        ->systemPrompt->toBe($systemPrompt)
        ->prompt->toBe($prompt)
        ->schema->toBe($schema)
        ->temperature->toBe($temperature)
        ->maxTokens->toBe($maxTokens)
        ->topP->toBe($topP)
        ->clientOptions->toBe($clientOptions)
        ->clientRetry->toBe($clientRetry)
        ->mode->toBe(StructuredMode::Auto)
        ->and($request->providerMeta(Provider::OpenAI))->toBe($providerMeta);
});

test('it generates and delegates to generator', function (): void {
    $provider = mock(\EchoLabs\Prism\Contracts\Provider::class);
    App::instance(PrismManager::class, new class($provider) extends PrismManager
    {
        public function __construct(private $provider) {}

        public function resolve($name, $config = []): \EchoLabs\Prism\Contracts\Provider
        {
            return $this->provider;
        }
    });

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withSchema(new StringSchema('test', 'test description'))
        ->withPrompt('Test prompt');

    $provider->shouldReceive('structured')->once()->andReturn(
        new ProviderResponse(
            text: json_encode(['test', 'description']),
            toolCalls: [],
            usage: new \EchoLabs\Prism\ValueObjects\Usage(1, 1),
            finishReason: \EchoLabs\Prism\Enums\FinishReason::Stop,
            responseMeta: new ResponseMeta('test', 'test'),
        )
    );

    $response = $request->generate();

    expect($response)
        ->toBeInstanceOf(Response::class);
});
