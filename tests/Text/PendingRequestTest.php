<?php

declare(strict_types=1);

use EchoLabs\Prism\Contracts\Provider as ProviderContract;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Text\PendingRequest;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Contracts\View\View;
use Tests\TestDoubles\TestProvider;

beforeEach(function (): void {
    $this->pendingRequest = new PendingRequest;
    $this->provider = new TestProvider;
});

test('it can configure the provider and model', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4');

    $generated = $request->toRequest();

    expect($generated->model)->toBe('gpt-4');
});

test('it sets provider meta', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withProviderMeta(Provider::OpenAI, ['key' => 'value']);

    $generated = $request->toRequest();

    expect($generated->providerMeta)
        ->toHaveKey('openai', ['key' => 'value']);
});

test('it allows you to get the model and provider', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4');

    expect($request->model())->toBe('gpt-4');
    expect($request->providerKey())->toBe('openai');
});

test('it configures the client options', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withClientOptions(['timeout' => 30]);

    $generated = $request->toRequest();

    expect($generated->clientOptions)
        ->toBe(['timeout' => 30]);
});

test('it configures client retry', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withClientRetry(3, 100);

    $generated = $request->toRequest();

    expect($generated->clientRetry)
        ->toBe([3, 100, null, true]);
});

test('it sets max tokens', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withMaxTokens(100);

    $generated = $request->toRequest();

    expect($generated->maxTokens)->toBe(100);
});

test('it sets temperature', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->usingTemperature(0.7);

    $generated = $request->toRequest();

    expect($generated->temperature)->toBe(0.7);
});

test('it sets top p', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->usingTopP(0.9);

    $generated = $request->toRequest();

    expect($generated->topP)->toBe(0.9);
});

test('it sets max steps', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withMaxSteps(5);

    $generated = $request->toRequest();

    expect($generated->maxSteps)->toBe(5);
});

test('it can add a tool', function (): void {
    $tool = Tool::as('test')
        ->for('test tool')
        ->using(fn (): string => 'test result');

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withTools([$tool]);

    $generated = $request->toRequest();

    expect($generated->tools)
        ->toHaveCount(1)
        ->and($generated->tools[0]->name())
        ->toBe('test');
});

test('it sets tool choice', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withToolChoice(ToolChoice::Auto);

    $generated = $request->toRequest();

    expect($generated->toolChoice)
        ->toBe(ToolChoice::Auto);
});

test('it can set string prompt', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withPrompt('Hello AI');

    $generated = $request->toRequest();

    expect($generated->prompt)->toBe('Hello AI')
        ->and($generated->messages[0])->toBeInstanceOf(UserMessage::class);
});

test('it can set view prompt', function (): void {
    $view = Mockery::mock(View::class);
    $view->shouldReceive('render')->andReturn('Hello AI');

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withPrompt($view);

    $generated = $request->toRequest();

    expect($generated->prompt)->toBe('Hello AI')
        ->and($generated->messages[0])->toBeInstanceOf(UserMessage::class);
});

test('it can set string system prompt', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withSystemPrompt('System instruction');

    $generated = $request->toRequest();

    expect($generated->systemPrompt)
        ->toBe('System instruction');
});

test('it can set view system prompt', function (): void {
    $view = Mockery::mock(View::class);
    $view->shouldReceive('render')->andReturn('System instruction');

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withSystemPrompt($view);

    $generated = $request->toRequest();

    expect($generated->systemPrompt)
        ->toBe('System instruction');
});

test('it can set messages', function (): void {
    $messages = [
        new SystemMessage('system'),
        new UserMessage('user'),
        new AssistantMessage('assistant'),
    ];

    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withMessages($messages);

    $generated = $request->toRequest();

    expect($generated->messages)
        ->toHaveCount(3)
        ->sequence(
            fn ($message) => $message->toBeInstanceOf(SystemMessage::class),
            fn ($message) => $message->toBeInstanceOf(UserMessage::class),
            fn ($message) => $message->toBeInstanceOf(AssistantMessage::class),
        );
});

test('it throws exception when using both prompt and messages', function (): void {
    $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withPrompt('test')
        ->withMessages([new UserMessage('test')])
        ->toRequest();
})->throws(PrismException::class, 'You can only use `prompt` or `messages`');

test('it throws exception when using both messages and prompt', function (): void {
    $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withMessages([new UserMessage('test')])
        ->withPrompt('test')
        ->toRequest();
})->throws(PrismException::class, 'You can only use `prompt` or `messages`');

test('it generates response', function (): void {
    resolve('prism-manager')->extend('test-provider', fn ($config): ProviderContract => new TestProvider);

    $response = $this->pendingRequest
        ->using('test-provider', 'test-model')
        ->withPrompt('test')
        ->generate();

    expect($response->text)->toBe("I'm nyx!");
});

test('you can run toRequest multiple times', function (): void {
    $request = $this->pendingRequest
        ->using(Provider::OpenAI, 'gpt-4')
        ->withPrompt('Hello AI');

    $request->toRequest();
    $request->toRequest();
})->throwsNoExceptions();
