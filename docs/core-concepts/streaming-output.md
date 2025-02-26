# Streaming Output

Want to show AI responses to your users in real-time? Streaming lets you display text as it's generated, creating a more responsive and engaging user experience.

## Basic Streaming

At its simplest, streaming works like this:

```php
use EchoLabs\Prism\Prism;

$response = Prism::stream()
    ->using('openai', 'gpt-4')
    ->withPrompt('Tell me a story about a brave knight.')
    ->generate();

// Process each chunk as it arrives
foreach ($response as $chunk) {
    echo $chunk->text;
    // Flush the output buffer to send text to the browser immediately
    ob_flush();
    flush();
}
```

## Understanding Chunks

Each chunk from the stream contains a piece of the generated content:

```php
foreach ($response as $chunk) {
    // The text fragment in this chunk
    echo $chunk->text;
    
    // Check if this is the final chunk
    if ($chunk->finishReason) {
        echo "Generation complete: " . $chunk->finishReason->name;
    }
}
```

## Streaming with Tools

Streaming works seamlessly with tools, allowing real-time interaction:

```php
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;

$weatherTool = Tool::as('weather')
    ->for('Get current weather information')
    ->withStringParameter('city', 'City name')
    ->using(function (string $city) {
        return "The weather in {$city} is sunny and 72°F.";
    });

$response = Prism::stream()
    ->using('openai', 'gpt-4o')
    ->withTools([$weatherTool])
    ->withMaxSteps(3) // Control maximum number of back-and-forth steps
    ->withPrompt('What\'s the weather like in San Francisco today?')
    ->generate();

$fullResponse = '';
foreach ($response as $chunk) {
    // Append each chunk to build the complete response
    $fullResponse .= $chunk->text;
    
    // Check for tool calls
    if ($chunk->toolCalls) {
        foreach ($chunk->toolCalls as $call) {
            echo "Tool called: " . $call->name;
        }
    }
    
    // Check for tool results
    if ($chunk->toolResults) {
        foreach ($chunk->toolResults as $result) {
            echo "Tool result: " . $result->result;
        }
    }
}

echo "Final response: " . $fullResponse;
```

## Handling Streaming in Web Applications

Here's how to integrate streaming in a Laravel controller:

```php
use EchoLabs\Prism\Prism;
use Illuminate\Http\Response;

public function streamResponse()
{
    return response()->stream(function () {
        $stream = Prism::stream()
            ->using('openai', 'gpt-4')
            ->withPrompt('Explain quantum computing step by step.')
            ->generate();
            
        foreach ($stream as $chunk) {
            echo $chunk->text;
            ob_flush();
            flush();
        }
    }, 200, [
        'Cache-Control' => 'no-cache',
        'Content-Type' => 'text/event-stream',
        'X-Accel-Buffering' => 'no', // Prevents Nginx from buffering
    ]);
}
```

Streaming gives your users a more responsive experience by showing AI-generated content as it's created, rather than making them wait for the complete response. This approach feels more natural and keeps users engaged, especially for longer responses or complex interactions with tools.
