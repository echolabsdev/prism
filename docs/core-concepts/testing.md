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
use EchoLabs\Prism\Text\Response as TextResponse;

it('can generate text', function () {
    // Create a fake text response
    $fakeResponse = new TextResponse(
        text: 'Hello, I am Claude!',
        steps: collect([]),
        responseMessages: collect([]),
        toolCalls: [],
        toolResults: [],
        usage: new Usage(10, 20),
        finishReason: FinishReason::Stop,
        responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        messages: collect([]),
        additionalContent: []
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
use EchoLabs\Prism\Text\Response as TextResponse;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;

it('can handle tool calls', function () {
    $responses = [
        new TextResponse(
            text: '',
            steps: collect([]),
            responseMessages: collect([]),
            toolCalls: [
                new ToolCall(
                    id: 'call_1',
                    name: 'search',
                    arguments: ['query' => 'Latest news']
                )
            ],
            toolResults: [],
            usage: new Usage(15, 25),
            finishReason: FinishReason::ToolCalls,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
            messages: collect([]),
            additionalContent: []
        ),
        new TextResponse(
            text: 'Here are the latest news...',
            steps: collect([]),
            responseMessages: collect([]),
            toolCalls: [],
            toolResults: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-2', 'fake-model'),
            messages: collect([]),
            additionalContent: []
        ),
    ];

    $fake = Prism::fake($responses);
});
```

## Using the ResponseBuilder

If you need to test a richer response object, e.g. with Steps, you may find it easier to use the ResponseBuilder:

```php
use EchoLabs\Prism\Text\ResponseBuilder;
use EchoLabs\Prism\Text\Step;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;

Prism::fake([
    (new ResponseBuilder)
        ->addStep(new Step(
            text: "Step 1 response text",
            finishReason: FinishReason::Stop,
            toolCalls: [/** tool calls */],
            toolResults: [/** tool results */],
            usage: new Usage(1000, 750),
            responseMeta: new ResponseMeta(id: 123, model: 'test-model'),
            messages: [
                new UserMessage('Test message 1', [
                    new Document(
                        document: '', 
                        mimeType: 'text/plain', 
                        dataFormat: 'text', 
                        documentTitle: 'Test document', 
                        documentContext: 'Test context'
                    ),
                ]),
                new AssistantMessage('Test message 2')
            ],
            systemPrompts: [
                new SystemMessage('Test system')
            ],
            additionalContent: ['test' => 'additional']
        ))
        ->addStep(new Step(
            text: "Step 2 response text",
            finishReason: FinishReason::Stop,
            toolCalls: [/** tool calls */],
            toolResults: [/** tool results */],
            usage: new Usage(1000, 750),
            responseMeta: new ResponseMeta(id: 123, model: 'test-model'),
            messages: [/** Second step messages */],
            systemPrompts: [/** Second step system prompts */],
            additionalContent: [/** Second step additional data */]
        ))
        ->toResponse()
]);
```

## Testing Tools

When testing tools, you'll want to verify both the tool calls and their results. Here's a complete example:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Text\Response as TextResponse;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;

it('can use weather tool', function () {
    // Define the expected tool call and response sequence
    $responses = [
        // First response: AI decides to use the weather tool
        new TextResponse(
            text: '', // Empty text since the AI is using a tool
            steps: collect([]),
            responseMessages: collect([]),
            toolCalls: [
                new ToolCall(
                    id: 'call_123',
                    name: 'weather',
                    arguments: ['city' => 'Paris']
                )
            ],
            toolResults: [],
            usage: new Usage(15, 25),
            finishReason: FinishReason::ToolCalls,
            responseMeta: new ResponseMeta('fake-1', 'fake-model'),
            messages: collect([]),
            additionalContent: []
        ),
        // Second response: AI uses the tool result to form a response
        new TextResponse(
            text: 'Based on current conditions, the weather in Paris is sunny with a temperature of 72°F.',
            steps: collect([]),
            responseMessages: collect([]),
            toolCalls: [],
            toolResults: [],
            usage: new Usage(20, 30),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('fake-2', 'fake-model'),
            messages: collect([]),
            additionalContent: []
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
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
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

    $fakeResponse = new StructuredResponse(
        steps: collect([]),
        responseMessages: collect([]),
        text: json_encode([
            'name' => 'Alice Tester',
            'bio' => 'Professional bug hunter and code wrangler'
        ]),
        structured: [
            'name' => 'Alice Tester',
            'bio' => 'Professional bug hunter and code wrangler'
        ],
        finishReason: FinishReason::Stop,
        usage: new Usage(10, 20),
        responseMeta: new ResponseMeta('fake-1', 'fake-model'),
        additionalContent: []
    );

    $fake = Prism::fake([$fakeResponse]);

    $response = Prism::structured()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('Generate a user profile')
        ->withSchema($schema)
        ->generate();

    // Assertions
    expect($response->structured)->toBeArray();
    expect($response->structured['name'])->toBe('Alice Tester');
    expect($response->structured['bio'])->toBe('Professional bug hunter and code wrangler');
});
```

## Assertions

PrismFake provides several helpful assertion methods:

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
