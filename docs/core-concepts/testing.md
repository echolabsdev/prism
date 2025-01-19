# Testing

Want to make sure your Prism integrations work flawlessly? Let's dive into testing! Prism provides a powerful fake implementation that makes it a breeze to test your AI-powered features.

## Basic Test Setup

First, let's look at how to set up basic response faking:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;

it('can generate text', function () {
    // Create a fake provider response
    $fakeResponse = new ProviderResponse(
        text: 'Hello, I am Claude!',
        toolCalls: [],
        usage: new Usage(10, 20),
        finishReason: FinishReason::Stop,
        responseMeta: new ResponseMeta('fake-1', 'fake-model')
    );

    // Set up the fake
    $fake = Prism::fake([$fakeResponse]);

    // Run your code
    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Who are you?')
        ->generate();

    // Make assertions
    expect($response->text)->toBe('Hello, I am Claude!');
});
```

## Testing Multiple Responses

When testing conversations or tool usage, you might need to simulate multiple responses:

```php
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\Providers\ProviderResponse;

it('can handle tool calls', function () {
    $responses = [
        new ProviderResponse(
            text: '',
            toolCalls: [
                new ToolCall(
                    id: 'call_1',
                    name: 'search',
                    arguments: ['query' => 'Latest news']
                )
            ],
            usage: new Usage(15, 25),
            finishReason: FinishReason::ToolCalls,
            responseMeta: new ResponseMeta('fake-1', 'fake-model')
        ),
        new ProviderResponse(
            text: 'Here are the latest news...',
            toolCalls: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-2', 'fake-model')
        ),
    ];

    $fake = Prism::fake($responses);
});
```

## Testing Tools

When testing tools, you'll want to verify both the tool calls and their results. Here's a complete example:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\Providers\ProviderResponse;

it('can use weather tool', function () {
    // Define the expected tool call and response sequence
    $responses = [
        // First response: AI decides to use the weather tool
        new ProviderResponse(
            text: '', // Empty text since the AI is using a tool
            toolCalls: [
                new ToolCall(
                    id: 'call_123',
                    name: 'weather',
                    arguments: ['city' => 'Paris']
                )
            ],
            usage: new Usage(15, 25),
            finishReason: FinishReason::ToolCalls,
            responseMeta: new ResponseMeta('fake-1', 'fake-model')
        ),
        // Second response: AI uses the tool result to form a response
        new ProviderResponse(
            text: 'Based on current conditions, the weather in Paris is sunny with a temperature of 72°F.',
            toolCalls: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-2', 'fake-model')
        ),
    ];

    // Set up the fake
    $fake = Prism::fake($responses);

    // Create the weather tool
    $weatherTool = Tool::as('weather')
        ->for('Get weather information')
        ->withStringParameter('city', 'City name')
        ->using(fn (string $city) => "The weather in {$city} is sunny with a temperature of 72°F");

    // Run the actual test
    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('What\'s the weather in Paris?')
        ->withTools([$weatherTool])
        ->generate();

    // Assert the correct number of API calls were made
    $fake->assertCallCount(2);

    // Assert tool calls were made correctly
    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('weather');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe(['city' => 'Paris']);

    // Assert tool results were processed
    expect($response->toolResults)->toHaveCount(1);
    expect($response->toolResults[0]->result)
        ->toBe('The weather in Paris is sunny with a temperature of 72°F');

    // Assert final response
    expect($response->text)
        ->toBe('Based on current conditions, the weather in Paris is sunny with a temperature of 72°F.');
});
```

## Testing Structured Output

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;

it('can generate structured response', function () {
    $schema = new ObjectSchema(
        name: 'user',
        description: 'A user object, because we love organizing things!',
        properties: [
            new StringSchema('name', 'The user\'s name (hopefully not "test test")'),
            new StringSchema('bio', 'A brief bio (no novels, please)'),
        ],
        requiredFields: ['name', 'bio']
    );

    $fakeResponse = new ProviderResponse(
        text: json_encode([
            'name' => 'Alice Tester',
            'bio' => 'Professional bug hunter and code wrangler'
        ]),
        toolCalls: [],
        usage: new Usage(10, 20),
        finishReason: FinishReason::Stop,
        responseMeta: new ResponseMeta('fake-1', 'fake-model')
    );

    $fake = Prism::fake([$fakeResponse]);

    $response = Prism::structured()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('Generate a user profile')
        ->withSchema($schema)
        ->generate();

    // Assertions
    expect($response->object)->toBeArray();
    expect($response->object['name'])->toBe('Alice Tester')
    expect($response->object['bio'])->toBe('Professional bug hunter and code wrangler');
});
```

## Assertions

Prism's fake implementation provides several helpful assertion methods:

```php
// Assert specific prompt was sent
$fake->assertPrompt('Who are you?');

// Assert number of calls made
$fake->assertCallCount(2);

// Assert detailed request properties
$fake->assertRequest(function ($requests) {
    expect($requests[0]->provider)->toBe('anthropic');
    expect($requests[0]->model)->toBe('claude-3-sonnet');
});

// Assert provider configuration
$fake->assertProviderConfig(['api_key' => 'sk-1234']);
```
