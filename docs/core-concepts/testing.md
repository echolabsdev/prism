# Testing

Want to make sure your Prism integrations work flawlessly? Let's dive into testing! Prism provides a powerful fake implementation that makes it a breeze to test your AI-powered features.

## Basic Test Setup

First, let's look at how to set up basic response faking:

```php
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;

public function test_can_generate_text(): void
{
    // Create a fake provider response
    $fakeResponse = new ProviderResponse(
        text: 'Hello, I am Claude!',
        toolCalls: [],
        usage: new Usage(10, 20),
        finishReason: FinishReason::Stop,
        response: ['id' => 'fake-1', 'model' => 'fake-model']
    );

    // Set up the fake
    $fake = Prism::fake([$fakeResponse]);

    // Run your code
    $response = Prism::text()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('Who are you?')
        ->generate();

    // Make assertions
    $this->assertEquals('Hello, I am Claude!', $response->text);
}
```

## Testing Multiple Responses

When testing conversations or tool usage, you might need to simulate multiple responses:

```php
public function test_can_handle_tool_calls(): void
{
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
            response: ['id' => 'fake-1', 'model' => 'fake-model']
        ),
        new ProviderResponse(
            text: 'Here are the latest news...',
            toolCalls: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake-2', 'model' => 'fake-model']
        ),
    ];

    $fake = Prism::fake($responses);
}
```

## Testing Tools

When testing tools, you'll want to verify both the tool calls and their results. Here's a complete example:

```php
public function test_can_use_weather_tool(): void
{
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
            response: ['id' => 'fake-1', 'model' => 'fake-model']
        ),
        // Second response: AI uses the tool result to form a response
        new ProviderResponse(
            text: 'Based on current conditions, the weather in Paris is sunny with a temperature of 72°F.',
            toolCalls: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake-2', 'model' => 'fake-model']
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
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('What\'s the weather in Paris?')
        ->withTools([$weatherTool])
        ->generate();

    // Assert the correct number of API calls were made
    $fake->assertCallCount(2);

    // Assert tool calls were made correctly
    $this->assertCount(1, $response->steps[0]->toolCalls);
    $this->assertEquals('weather', $response->steps[0]->toolCalls[0]->name);
    $this->assertEquals(['city' => 'Paris'], $response->steps[0]->toolCalls[0]->arguments());

    // Assert tool results were processed
    $this->assertCount(1, $response->toolResults);
    $this->assertEquals(
        'The weather in Paris is sunny with a temperature of 72°F',
        $response->toolResults[0]->result
    );

    // Assert final response
    $this->assertEquals(
        'Based on current conditions, the weather in Paris is sunny with a temperature of 72°F.',
        $response->text
    );
}
```

## Testing Structured Output

```php
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;

public function test_can_generate_structured_response(): void
{
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
        usage: new Usage(10, 20), // Look ma, I'm counting tokens!
        finishReason: FinishReason::Stop,
        response: ['id' => 'fake-1', 'model' => 'fake-model']
    );

    $fake = Prism::fake([$fakeResponse]);

    $response = Prism::structured() // Notice the structured() instead of text()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('Generate a user profile')
        ->withSchema($schema)
        ->generate();

    // Time for some good old-fashioned assertions
    // (Trust but verify, as my therapist says)
    $this->assertIsArray($response->object);
    $this->assertEquals('Alice Tester', $response->object['name']);
    $this->assertEquals(
        'Professional bug hunter and code wrangler',
        $response->object['bio']
    );
}
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
    $this->assertEquals('anthropic', $requests[0]->provider);
    $this->assertEquals('claude-3-sonnet', $requests[0]->model);
});
```
