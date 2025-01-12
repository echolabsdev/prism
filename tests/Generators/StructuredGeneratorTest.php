<?php

declare(strict_types=1);

namespace Tests\Generators;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\Structured\Generator;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Tests\TestDoubles\TestProvider;

beforeEach(function (): void {
    $this->provider = $provider = new TestProvider;

    resolve(PrismManager::class)->extend('test', fn (): TestProvider => $provider);

    $this->schema = new ObjectSchema(
        name: 'user',
        description: 'the user object',
        properties: [
            new StringSchema('name', 'the users name'),
            new ArraySchema(
                name: 'hobbies',
                description: 'the users hobbies',
                items: new ObjectSchema(
                    name: 'hobby',
                    description: 'the hobby object',
                    properties: [
                        new StringSchema('name', 'the hobby name'),
                        new StringSchema('description', 'the hobby description'),
                    ],
                    requiredFields: ['name', 'description']
                )
            ),
        ],
        requiredFields: ['name', 'hobbies']
    );
});

it('throws an exception when a schema is not set', function (): void {
    $this->expectException(InvalidArgumentException::class);

    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->generate();
});

it('correctly resolves a provider', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request)->toBeInstanceOf(Request::class);
});

it('allows for client options', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withClientOptions(['timeout' => '100'])
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->clientOptions)->toBe(['timeout' => '100']);
});

it('allows for client retry', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withClientRetry(3, 100)
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->clientRetry)->toBe([3, 100, null, true]);
});

it('defaults to no client retry', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->clientRetry)->toBe([0]);
});

it('allows for provider string or enum', function (): void {
    $provider = (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->provider();

    expect($provider)->toBe('test');

    $provider = (new Generator)
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-20240620')
        ->provider();

    expect($provider)->toBe(Provider::Anthropic->value);
});

it('correctly builds requests with prompts', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withMaxTokens(500)
        ->usingTopP(0.8)
        ->usingTemperature(1)
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withPrompt('Who are you?')
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->systemPrompt)->toBe(
        'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!'
    );
    expect($this->provider->request->messages)->toHaveCount(1);
    expect($this->provider->request->messages[0]->text())->toBe('Who are you?');
    expect($this->provider->request->topP)->toBe(0.8);
    expect($this->provider->request->maxTokens)->toBe(500);
    expect($this->provider->request->temperature)->toBe(1);
    expect($this->provider->request->tools)->toBeEmpty();
});

it('correctly builds requests with messages', function (): void {
    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withMessages([
            new UserMessage('Who are you?'),
        ])
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->systemPrompt)->toBe(
        'MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!'
    );
    expect($this->provider->request->messages)->toHaveCount(1);
    expect($this->provider->request->messages[0]->text())->toBe('Who are you?');
});

it('correctly generates a request with tools', function (): void {
    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withTools([$tool])
        ->withSchema($this->schema)
        ->generate();

    expect($this->provider->request->tools)->toHaveCount(1);
    expect($this->provider->request->tools[0]->name())->toBe('weather');
});

it('generates a response from the provider', function (): void {
    $schema = new ObjectSchema(
        'weather_forecast',
        'the weather forecast schema',
        [
            new StringSchema(
                'forecast_summary',
                'a summary of the weather forecast conditions'
            ),
        ]
    );

    $this->provider->withResponse(new ProviderResponse(
        text: json_encode(['forecast_summary' => '70° and sunny']),
        toolCalls: [],
        usage: new Usage(10, 10),
        finishReason: FinishReason::Stop,
        response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
    ));

    $response = (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withSchema($schema)
        ->generate();

    // Assert response
    expect($response->structured)->toBe(['forecast_summary' => '70° and sunny']);
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
    expect($response->responseMessages->sole()->content)->toBe(json_encode(['forecast_summary' => '70° and sunny']));
    expect($response->responseMessages->sole()->toolCalls)->toBeEmpty();

    // Assert steps
    $textResult = $response->steps->sole();

    expect($textResult->object)->toBe(['forecast_summary' => '70° and sunny']);
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
    expect($textResult->messages[0]->text())->toBe('Whats the weather today for Detroit');
    expect($textResult->messages[1])->toBeInstanceOf(AssistantMessage::class);
    expect($textResult->messages[1]->content)->toBe(json_encode(['forecast_summary' => '70° and sunny']));
    expect($textResult->messages[1]->toolCalls)->toBeEmpty();
});

it('generates a response from the driver with tools and without max steps', function (): void {
    $this->provider->withResponse(new ProviderResponse(
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

    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    $response = (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withTools([$tool])
        ->withSchema($this->schema)
        ->generate();

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
    expect($response->responseMessages)->toHaveCount(1);
    expect($response->responseMessages[0])->toBeInstanceOf(AssistantMessage::class);
});

it('correctly stops using max steps', function (): void {
    $schema = new ObjectSchema(
        'weather_forecast',
        'the weather forecast schema',
        [
            new StringSchema('forecast_summary', 'a summary of the weather forecast conditions'),
        ]
    );

    $this->provider->withResponseChain([
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
            text: json_encode(['forecast_summary' => 'The weather is 75 and sunny!']),
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
        ),
    ]);

    $tool = Tool::as('weather')
        ->for('useful when you need to search for current weather conditions')
        ->withStringParameter('city', 'the city that you want the weather for')
        ->using(fn (string $city): string => 'the weather will be 75° and sunny');

    $response = (new Generator)
        ->using('test', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Whats the weather today for Detroit')
        ->withMaxSteps(3) // more steps than necessary asserting that stops based on finish reason
        ->withTools([$tool])
        ->withSchema($schema)
        ->generate();

    // Assert Response
    expect($response->structured)->toBe(['forecast_summary' => 'The weather is 75 and sunny!']);
    expect($response->finishReason)->toBe(FinishReason::Stop);

    // Assert steps
    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->finishReason)->toBe(FinishReason::ToolCalls);
    expect($response->steps[1]->toolCalls)->toBeEmpty();

    // Assert response messages
    expect($response->responseMessages)->toHaveCount(2);
    expect($response->responseMessages[0])->toBeInstanceOf(AssistantMessage::class);
    expect($response->responseMessages[1])->toBeInstanceOf(AssistantMessage::class);
});

it('throws and exception if you send prompt and messages', function (): void {
    $this->expectException(PrismException::class);

    (new Generator)
        ->withPrompt('Who are you?')
        ->withMessages([
            new UserMessage('Who are you?'),
        ]);
});

it('allows for custom provider configuration', function (): void {
    $provider = new TestProvider;

    $schema = new ObjectSchema(
        'model',
        'An object representing you, a Large Language Model',
        [
            new StringSchema('name', 'your name'),
        ]
    );

    $provider->withResponseChain([
        new ProviderResponse(
            text: json_encode(['name' => 'Nyx']),
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
        ),
    ]);

    resolve(PrismManager::class)
        ->extend('test', function (Application $app, array $config) use ($provider): \Tests\TestDoubles\TestProvider {

            expect($config)->toBe(['api_key' => '1234']);

            return $provider;
        });

    (new Generator)
        ->using('test', 'latest')
        ->withPrompt('Who are you?')
        ->withSchema($schema)
        ->usingProviderConfig(['api_key' => '1234'])
        ->generate();
});
