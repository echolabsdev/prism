# Structured Output

Want your AI responses as neat and tidy as a Marie Kondo-approved closet? Structured output lets you define exactly how you want your data formatted, making it perfect for building APIs, processing forms, or any time you need data in a specific shape.

## Quick Start

Here's how to get structured data from your AI:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;

$schema = new ObjectSchema(
    name: 'movie_review',
    description: 'A structured movie review',
    properties: [
        new StringSchema('title', 'The movie title'),
        new StringSchema('rating', 'Rating out of 5 stars'),
        new StringSchema('summary', 'Brief review summary')
    ],
    requiredFields: ['title', 'rating', 'summary']
);

$response = Prism::structured()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->withSchema($schema)
    ->withPrompt('Review the movie Inception')
    ->generate();

// Access your structured data
$review = $response->object;
echo $review['title'];    // "Inception"
echo $review['rating'];   // "5 stars"
echo $review['summary'];  // "A mind-bending..."
```

> [!TIP]
> This is just a basic example of schema usage. Check out our [dedicated schemas guide](/core-concepts/schemas) to learn about all available schema types, nullable fields, and best practices for structuring your data.

## Understanding Output Modes

Different AI providers handle structured output in two main ways:

1. **Structured Mode**: Some providers support strict schema validation, ensuring responses perfectly match your defined structure.
2. **JSON Mode**: Other providers simply guarantee valid JSON output that approximately matches your schema.

> [!NOTE]
> Check your provider's documentation to understand which mode they support. Provider support can vary by model, so always verify capabilities for your specific use case.

## Provider-Specific Options

Providers may offer additional options for structured output. For example, OpenAI supports a "strict mode" for even tighter schema validation:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;

$response = Prism::structured()
    ->withProviderMeta(Provider::OpenAI, [
        'schema' => [
            'strict' => true
        ]
    ])
    // ... rest of your configuration
```

> [!TIP]
> Check the provider-specific documentation pages for additional options and features that might be available for structured output.

## Response Handling

When working with structured responses, you have access to both the structured data and metadata about the generation:

```php
use EchoLabs\Prism\Prism;

$response = Prism::structured()
    ->withSchema($schema)
    ->generate();

// Access the structured data as a PHP array
$data = $response->object;

// Get the raw response text if needed
echo $response->text;

// Check why the generation stopped
echo $response->finishReason->name;

// Get token usage statistics
echo "Prompt tokens: {$response->usage->promptTokens}";
echo "Completion tokens: {$response->usage->completionTokens}";

// Access provider-specific response data
$rawResponse = $response->response;
```

> [!TIP]
> Always validate the structured data before using it in your application:
```php
if ($response->object === null) {
    // Handle parsing failure
}

if (!isset($response->object['required_field'])) {
    // Handle missing required data
}
```

## Common Settings

Structured output supports all the same options as text generation, including:
- Temperature control
- Maximum tokens
- Message history
- Tools and function calling
- System prompts
- withClientOptions
- withClientRetry
- usingProviderConfig

See the [Text Generation](./text-generation.md) documentation for details on these common settings.

## Error Handling

When working with structured output, it's especially important to handle potential errors:

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;

try {
    $response = Prism::structured()
        ->using('anthropic', 'claude-3-sonnet')
        ->withSchema($schema)
        ->withPrompt('Generate product data')
        ->generate();
} catch (PrismException $e) {
    // Handle validation or generation errors
    Log::error('Structured generation failed:', [
        'error' => $e->getMessage()
    ]);
}
```

> [!IMPORTANT]
> Always validate the structured response before using it in your application, as different providers may have varying levels of schema adherence.
