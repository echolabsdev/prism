# Introduction

Large Language Models (LLMs) have revolutionized how we interact with artificial intelligence, enabling applications to understand, generate, and manipulate human language with unprecedented sophistication. These powerful models open up exciting possibilities for developers, from creating chatbots and content generators to building complex AI-driven applications.

Prism **simplifies the process of integrating LLMs into your Laravel projects**, providing a unified interface to work with various AI providers. This allows you to focus on crafting innovative AI features for your users, rather than getting bogged down in the intricacies of different APIs and implementation details.

Here's a quick example of how you can generate text using Prism:

::: code-group
```php [Anthropic]
$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-sonnet')
    ->withSystemPrompt(view('prompts.system'))
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->generate();

echo $response->text;
```

```php [Mistral]
$response = Prism::text()
    ->using(Provider::Mistral, 'mistral-medium')
    ->withSystemPrompt(view('prompts.system'))
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->generate();

echo $response->text;
```

```php [Ollama]
$response = Prism::text()
    ->using(Provider::Ollama, 'llama2')
    ->withSystemPrompt(view('prompts.system'))
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->generate();

echo $response->text;
```

```php [OpenAI]
$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4')
    ->withSystemPrompt(view('prompts.system'))
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->generate();

echo $response->text;
```
:::

Prism draws significant inspiration from the [Vercel AI SDK](https://sdk.vercel.ai/docs/ai-sdk-core), adapting its powerful concepts and developer-friendly approach to the Laravel ecosystem.

## Key Features

- **Unified Provider Interface**: Switch seamlessly between AI providers like OpenAI, Anthropic, and Ollama without changing your application code.
- **Tool System**: Extend AI capabilities by defining custom tools that can interact with your application's business logic.
- **Image Support**: Work with multi-modal models that can process both text and images.

## Supported Providers

We currently offer first-party support for these leading AI providers:

- [Anthropic](https://anthropic.com) - Access Claude models for sophisticated reasoning and tool use
- [Groq](https://groq.com) - Leverage high-performance inference for rapid response times
- [Mistral](https://mistral.ai) - Use cutting-edge open models with commercial licensing
- [Ollama](https://ollama.com) - Run open-source models locally for complete control
- [OpenAI](https://openai.com) - Integrate with GPT-4 and other powerful models

Each provider brings its own strengths to the table, and Prism makes it easy to use them all through a consistent, elegajnt interface.
