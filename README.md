<p align="center">
    <img src="docs/images/sparkle-kitty-banner.webp" alt="Sparkle Kitty" width="100%" height="auto" />
</p>

# Sparkle

[Sponsor me!](https://github.com/sponsors/sixlive)

Sparkle provides a streamlined way to build AI-driven features in your application using Large Language Models (LLMs). This guide will walk you through creating and managing agents, defining tools, and interacting with various LLM providers, such as OpenAI.

## Getting Started

Before you begin, ensure you have installed the necessary packages and configured your application to use Sparkle.

### Installation

You can install Sparkle via Composer:

```bash
composer require echolabs/sparkle
```

After installation, you can publish the configuration file:

```bash
php artisan vendor:publish --provider="EchoLabs\Sparkle\SparkleServiceProvider"
```

This will create a `config/sparkle.php` file where you can configure your default LLM provider, model, and other settings. The provider also automatically registers the routes for Sparkle Server.

### Configuration

The configuration file allows you to set your default LLM provider and model:

```php
return [
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
        ],
    ],
];
```

Make sure to set the necessary API keys and environment variables for your chosen provider(s).

---

## Agents

Agents are the core of Sparkle. An agent encapsulates the logic for interacting with an LLM, including the prompt, options, and any tools it might use.

### Defining an Agent

To define an agent, create a class in your `App\Agents` directory:

```php
<?php

declare(strict_types=1);

namespace App\Agents;

use EchoLabs\Sparkle\Facades\Agent;
use EchoLabs\Sparkle\Facades\Tool;

class MultiToolAgent
{
    public function __invoke()
    {
        return Agent::provider('openai')
            ->using('gpt-4')
            ->withOptions([
                'top_p' => 1,
                'temperature' => 0.8,
                'max_tokens' => 2048,
            ])
            ->withPrompt($this->prompt())
            ->withTools($this->tools());
    }

    private function prompt(): string
    {
        $prompt = "MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]! \r\n";
        $prompt .= "Nyx is the cutest, most friendly, Cthulhu around. \r\n";
        $prompt .= 'The current date and time is '.now()->toDateTimeString();

        return $prompt;
    }

    /** @return array<int, Tool> */
    public function tools(): array
    {
        return [
            Tool::as('search')
                ->for('useful when you need to search for current events')
                ->withParameter('query', 'the search query string', 'string', true)
                ->using(function (string $query): string {
                    // simulate API request
                    sleep(3);

                    return 'The tigers game is at 3pm eastern in Detroit';
                }),
            Tool::as('weather')
                ->for('useful when you need to search for current weather conditions')
                ->withParameter('city', 'The city that you want the weather for', 'string', true)
                ->withParameter('datetime', 'the datetime for the weather conditions. format 2022-08-14 20:24:38', 'string', true)
                ->using(function (string $city, string $datetime): string {
                    // simulate API request
                    sleep(3);

                    return 'The weather will be 75° and sunny';
                }),
        ];
    }
}
```

### Understanding the Agent

- **Provider:** The LLM provider, such as OpenAI.
- **Model:** The specific model, like GPT-4.
- **Options:** Configuration options that modify the behavior of the LLM. (`max_tokens`, `temperature`)
- **Prompt:** The initial text prompt that sets the context for the model.
- **Tools:** Custom logic or external APIs that the agent can call.
- **Memory:** Memory holds the conversation history.

### Running an Agent

```php
<?php

(new MultiToolAgent)()
  ->run();

// or as a stream

(new MultiToolAgent)()
  ->stream();
```

### Memory

Agents use the `ArrayBuffer` memory driver by default. You can access the underlying memory provider via `$agent->memory()` which allows you to add messages ex. `$agent->memory()->addMessage(Message::user('Who are you?'))`.

#### Adding memory to an Agent

```php
<?php

use EchoLabs\Sparkle\MemoryProviders\ArrayBuffer;

$searchTool = Tool::as('search')
    ->for('useful when you need to search for current events')
    ->withParameter('query', 'the search query string', 'string', true)
    ->using(function (string $query): string {
        // simulate API request
        sleep(3);

        return 'The tigers game is at 3pm eastern in Detroit';
    });

$agent = Agent::provider('openai')
    ->using('gpt-4')
    ->withOptions([
        'top_p' => 1,
        'temperature' => 0.8,
        'max_tokens' => 2048,
    ])
    ->withPrompt(view('prompts.nova'))
    ->withTools([$searchTool])
    ->withMemory(new ArrayBuffer([
        Message::user('What time is the Tigers game today?'),
    ]));

  // The tigers game is scheduled for today at 3pm in Detroit
  dd($agent->run()->content);
```

#### Creating your own memory driver
For example, if you wanted to create an Eloquent memory driver you just need to implement the `MemoryProvider` contract.

```php
<?php

declare(strict_types=1);

namespace EchoLabs\Sparkle\Contracts;

use EchoLabs\Sparkle\Message;

interface MemoryProvider
{
    public function addMessage(Message $message): self;

    /** @param array<int, Message> $messages */
    public function addMessages(array $messages): self;

    /** @return array<int, Message> */
    public function messages(): array;
}
```

---

## Tools

Tools in Sparkle represent discrete units of functionality that an agent can use. A tool could be anything from a database query to an external API call.

### Creating a Tool

Tools are defined using the `Tool` class, allowing you to specify parameters and the logic that executes when the tool is called.

```php
<?php

Tool::as('search')
    ->for('useful when you need to search for current events')
    ->withParameter('query', 'The search query string', 'string')
    ->using(function (string $query): string {
        // Simulate an API call or other logic
        return 'The event is at 3pm eastern';
    });
```

### Tool Methods

- **`as(string $name)`**: Sets the tool's name.
- **`for(string $description)`**: Provides a description of the tool, guiding the LLM on when to use it.
- **`withParameter(string $name, string $description, string $type = 'string', bool $required = true)`**: Defines a parameter for the tool.
- **`using(Closure|Invokeable $fn)`**: Specifies the logic executed when the tool is invoked.

## Sparkle Server

Sparkle Server provides an easy way to expose your agents via an OpenAI-compatible chat API endpoint, supporting both streaming and non-streaming responses. This feature allows seamless integration with tools like Open Web UI or other platforms that expect an OpenAI-like API for interacting with LLMs.

![Sparkle Server Preview](/docs/realtime-chat.gif)

### Setting Up Sparkle Server

To get started with Sparkle Server, you'll need to register your agents within your application. This is typically done within a service provider.

### Registering Agents

Agents are registered with the `SparkleServer` facade in a service provider. Here's an example of how to register an agent:

```php
<?php

namespace App\Providers;

use App\Agents\MultiToolAgent;
use EchoLabs\Sparkle\Facades\SparkleServer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        SparkleServer::register(
            'agent-with-tools',
            new MultiToolAgent
        );
    }
}
```

In this example, we're registering an agent called `agent-with-tools` using the `MultiToolAgent` class. The name provided will be used as the model identifier when making API requests.

### API Usage

Once your agents are registered, you can interact with them through a Sparkle Server endpoint. The server is compatible with the OpenAI chat completion API, making it easy to integrate with existing tools and workflows. My personal favorite tool for this is [Open WebUI](https://openwebui.com/).

#### Chat Completion Request

To send a message to an agent, you can use a `POST` request to the `/sparkle/openai/v1/chat/completions` endpoint. Below is an example of such a request:

```bash
curl -X "POST" "http://localhost:8001/sparkle/openai/v1/chat/completions" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json; charset=utf-8' \
     -d $'{
  "model": "agent-with-tools",
  "stream": false,
  "messages": [
    {
      "content": "What time is the Tigers game today? Do I need a coat?",
      "role": "user"
    }
  ]
}'
```

##### Response

The server will respond with a JSON object containing the assistant's message, similar to the OpenAI API response format:

```json
{
  "id": "01J6APGE5YS8S04NN7N8REPPR6",
  "object": "chat.completion",
  "created": 1724788521,
  "model": "agent-with-tools",
  "usage": [],
  "choices": [
    {
      "index": 0,
      "delta": {
        "role": "assistant",
        "content": "The Tigers game is today at 3pm Eastern in Detroit. The weather will be 75° and sunny, so you shouldn't need a coat."
      },
      "finish_reason": "stop"
    }
  ]
}
```

#### Streaming Response

For applications that require real-time data, Sparkle Server also supports streaming responses. Here's how you would make a request that streams the response:

```bash
curl -X "POST" "http://localhost:8001/sparkle/openai/v1/chat/completions" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json; charset=utf-8' \
     -d $'{
  "model": "agent-with-tools",
  "stream": true,
  "messages": [
    {
      "content": "What time is the Tigers game today? Do I need a coat?",
      "role": "user"
    }
  ]
}'
```

##### Streaming Response

The server will stream back the response in chunks, allowing you to process the response as it's generated:

```
data: {"object":"chat.completion.chunk","model":"gpt-4","choices":[{"delta":{"role":"assistant","content":"The"}}]}
data: {"object":"chat.completion.chunk","model":"gpt-4","choices":[{"delta":{"role":"assistant","content":" Tigers"}}]}
data: {"object":"chat.completion.chunk","model":"gpt-4","choices":[{"delta":{"role":"assistant","content":" game"}}]}
...
data: {"object":"chat.completion.chunk","model":"gpt-4","choices":[{"delta":{"role":"assistant","content":"."}}]}
data: [DONE]
```

### Listing Registered Agents

To list all the agents registered with Sparkle Server, you can send a `GET` request to the `/sparkle/openai/v1/models` endpoint:

```bash
curl "http://localhost:8001/sparkle/openai/v1/models" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json; charset=utf-8'
```

##### Response

The server will return a list of all registered agents:

```json
{
  "object": "list",
  "data": [
    {
      "id": "agent-with-tools",
      "object": "model"
    }
  ]
}
```
