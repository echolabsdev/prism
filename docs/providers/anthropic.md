# Anthropic
## Configuration

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
]
```
## Prompt caching

Anthropic's prompt caching feature allows you to drastically reduce latency and your API bill when repeatedly re-using blocks of content within five minutes of each other.

We support Anthropic prompt caching on:

- System Messages (text only)
- User Messages (Text, Image and PDF (pdf only))
- Assistant Messages (text only)
- Tools

The API for enabling prompt caching is the same for all, enabled via the `withProviderMeta()` method. Where a UserMessage contains both text and an image or document, both will be cached.

```php
use EchoLabs\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;

Prism::text()
    ->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
    ->withMessages([
        (new SystemMessage('I am a long re-usable system message.'))
            ->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral']),

        (new UserMessage('I am a long re-usable user message.'))
            ->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral'])
    ])
    ->withTools([
        Tool::as('cache me')
            ->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral'])
    ])
    ->generate();
```

If you prefer, you can use the `AnthropicCacheType` Enum like so:

```php
use EchoLabs\Enums\Provider;
use EchoLabs\Prism\Providers\Anthropic\Enums\AnthropicCacheType;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

(new UserMessage('I am a long re-usable user message.'))->withProviderMeta(Provider::Anthropic, ['cacheType' => AnthropicCacheType::ephemeral])
```
Note that you must use the `withMessages()` method in order to enable prompt caching, rather than `withPrompt()` or `withSystemPrompt()`.

Please ensure you read Anthropic's [prompt caching documentation](https://docs.anthropic.com/en/docs/build-with-claude/prompt-caching), which covers some important information on e.g. minimum cacheable tokens and message order consistency.

### PDF Support

Prism supports Anthropic PDF processing on UserMessages via the `$additionalContent` parameter:

```php
use EchoLabs\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

Prism::text()
    ->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
    ->withMessages([
        new UserMessage('Here is the document from base64', [
            Document::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-pdf.pdf')), 'application/pdf'),
        ]),
        new UserMessage('Here is the document from a local path', [
            Document::fromPath('tests/Fixtures/test-pdf.pdf', 'application/pdf'),
        ]),
    ])
    ->generate();

```
Anthropic use vision to process PDFs, and consequently there are some limitations detailed in their [feature documentation](https://docs.anthropic.com/en/docs/build-with-claude/pdf-support).


## Considerations
### Message Order

- Message order matters. Anthropic is strict about the message order being:

1. `UserMessage`
2. `AssistantMessage`
3. `UserMessage`

### Structured Output

While Anthropic models don't have native JSON mode or structured output like some providers, Prism implements a robust workaround for structured output:

- We automatically append instructions to your prompt that guide the model to output valid JSON matching your schema
- If the response isn't valid JSON, Prism will raise a PrismException

## Limitations
### Messages

Most providers' API include system messages in the messages array with a "system" role. Anthropic does not support the system role, and instead has a "system" property, separate from messages.

Therefore, for Anthropic we:
* Filter all `SystemMessage`s out, omitting them from messages.
* Always submit the prompt defined with `->withSystemPrompt()` at the top of the system prompts array.
* Move all `SystemMessage`s to the system prompts array in the order they were declared.

### Images

Does not support `Image::fromURL`
