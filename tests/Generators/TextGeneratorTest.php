<?php

declare(strict_types=1);

namespace Tests\Generators;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Generators\TextGenerator;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Tests\TestDoubles\TestProvider;

it('correctly resolves a provider', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    (new TextGenerator)->using('test', 'claude-3-5-sonnet-20240620');

    expect($provider->model)->toBe('claude-3-5-sonnet-20240620');
});

it('allows for client options', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withClientOptions(['timeout' => '100'])
        ->generate();

    expect($provider->clientOptions)->toBe(['timeout' => '100']);
});

it('correctly builds requests', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withMaxTokens(500)
        ->usingTopP(0.8)
        ->usingTemperature(1)
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withPrompt('Who are you?')
        ->generate();

    expect($provider->request->systemPrompt)->toBe(
        'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!'
    );
    expect($provider->request->messages)->toHaveCount(1);
    expect($provider->request->messages[0]->content())->toBe('Who are you?');
    expect($provider->request->topP)->toBe(0.8);
    expect($provider->request->maxTokens)->toBe(500);
    expect($provider->request->temperature)->toBe(1);
    expect($provider->request->tools)->toBeEmpty();
});

it('correctly builds requests with messages', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withMessages([
            new UserMessage('Who are you?'),
        ])();

    expect($provider->request->systemPrompt)->toBe(
        'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!'
    );
    expect($provider->request->messages)->toHaveCount(1);
    expect($provider->request->messages[0]->content())->toBe('Who are you?');
});

it('correctly generates a request with tools', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withTools([$tool])();

    expect($provider->request->tools)->toHaveCount(1);
    expect($provider->request->tools[0]->name())->toBe('weather');
});

it('generates a response from the provider', function (): void {
    $provider = new TestProvider;

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    $response = (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')();

    // Assert response
    expect($response->text)->toBe("I'm nyx!");
    expect($response->finishReason)->toBe(FinishReason::Stop);
    expect($response->toolCalls)->toBeEmpty();
    expect($response->toolResults)->toBeEmpty();
    expect($response->response)->toBe([
        'id' => '123',
        'model' => 'claude-3-5-sonnet-20240620',
    ]);
    expect($response->usage)->toBeInstanceOf(Usage::class);

    // Assert response messages
    expect($response->responseMessages)->toHaveCount(1);
    expect($response->responseMessages->sole()->content())->toBe("I'm nyx!");
    expect($response->responseMessages->sole()->toolCalls())->toBeEmpty();

    // Assert steps
    $textResult = $response->steps->sole();

    expect($textResult->text)->toBe("I'm nyx!");
    expect($textResult->finishReason)->toBeInstanceOf(FinishReason::class);
    expect($textResult->finishReason->name)->toBe('Stop');
    expect($textResult->toolCalls)->toBeEmpty();
    expect($textResult->toolResults)->toBeEmpty();
    expect($textResult->usage)->toBeInstanceOf(Usage::class);
    expect($textResult->usage->promptTokens)->toBe(10);
    expect($textResult->usage->completionTokens)->toBe(10);
    expect($textResult->response)->toBeArray();
    expect($textResult->response)->toHaveCount(2);
    expect($textResult->response['id'])->toBe('123');
    expect($textResult->response['model'])->toBe('claude-3-5-sonnet-20240620');
    expect($textResult->messages)->toBeArray();
    expect($textResult->messages)->toHaveCount(2);
    expect($textResult->messages[0])->toBeInstanceOf(UserMessage::class);
    expect($textResult->messages[0]->content())->toBe('Whats the weather today for Detroit');
    expect($textResult->messages[1])->toBeInstanceOf(AssistantMessage::class);
    expect($textResult->messages[1]->content())->toBe("I'm nyx!");
    expect($textResult->messages[1]->toolCalls())->toBeEmpty();
});

it('generates a response from the driver with tools and max steps', function (): void {
    $provider = new TestProvider;

    $provider->withResponse(new ProviderResponse(
        text: '',
        toolCalls: [
            new ToolCall(
                id: 'tool_1234',
                name: 'weather',
                arguments: [
                    'city' => 'Detroit',
                ]
            ),
        ],
        usage: new Usage(10, 10),
        finishReason: FinishReason::ToolCalls,
        response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
    ));

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    $response = (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withTools([$tool])();

    // Assert response
    expect($response->text)->toBeEmpty();
    expect($response->finishReason)->toEqual(FinishReason::ToolCalls);
    expect($response->toolCalls)->toHaveCount(1);
    expect($response->toolResults)->toHaveCount(1);

    // Assert steps
    $step = $response->steps->sole();

    expect($step->text)->toBeEmpty();
    expect($step->finishReason)->toEqual(FinishReason::ToolCalls);
    expect($step->toolCalls)->toHaveCount(1);
    expect($step->toolResults)->toHaveCount(1);
    expect($step->messages)->toHaveCount(3);
    expect($step->messages[2])->toBeInstanceOf(ToolResultMessage::class);

    // Assert response messages
    expect($response->responseMessages)->toHaveCount(2);
    expect($response->responseMessages[0])->toBeInstanceOf(AssistantMessage::class);
    expect($response->responseMessages[1])->toBeInstanceOf(ToolResultMessage::class);
});

it('correctly stops using max steps', function (): void {
    $provider = new TestProvider;

    $provider->withResponseChain([
        new ProviderResponse(
            text: '',
            toolCalls: [
                new ToolCall(
                    id: 'tool_1234',
                    name: 'weather',
                    arguments: [
                        'city' => 'Detroit',
                    ]
                ),
            ],
            usage: new Usage(10, 10),
            finishReason: FinishReason::ToolCalls,
            response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
        ),
        new ProviderResponse(
            text: 'The weather is 75 and sunny!',
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
        ),
    ]);

    resolve('prism-manager')->extend('test', fn (): \Tests\TestDoubles\TestProvider => $provider);

    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    $response = (new TextGenerator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withMaxSteps(3) // more steps than necessary asserting that stops based on finish reason
        ->withTools([$tool])();

    // Assert Response
    expect($response->text)->toBe('The weather is 75 and sunny!');
    expect($response->finishReason)->toBe(FinishReason::Stop);

    // Assert steps
    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->finishReason)->toBe(FinishReason::ToolCalls);
    expect($response->steps[1]->toolCalls)->toBeEmpty();

    // Assert response messages
    expect($response->responseMessages)->toHaveCount(3);
    expect($response->responseMessages[0])->toBeInstanceOf(AssistantMessage::class);
    expect($response->responseMessages[1])->toBeInstanceOf(ToolResultMessage::class);
    expect($response->responseMessages[2])->toBeInstanceOf(AssistantMessage::class);
});

it('throws and exception if you send prompt and messages', function (): void {
    $this->expectException(PrismException::class);

    (new TextGenerator)
        ->withPrompt('Who are you?')
        ->withMessages([
            new UserMessage('Who are you?'),
        ]);
});
