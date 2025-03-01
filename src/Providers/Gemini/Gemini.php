<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Gemini;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\Contracts\Provider;
use PrismPHP\Prism\Embeddings\Request as EmbeddingRequest;
use PrismPHP\Prism\Embeddings\Response as EmbeddingResponse;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\Gemini\Handlers\Cache;
use PrismPHP\Prism\Providers\Gemini\Handlers\Embeddings;
use PrismPHP\Prism\Providers\Gemini\Handlers\Structured;
use PrismPHP\Prism\Providers\Gemini\Handlers\Text;
use PrismPHP\Prism\Providers\Gemini\ValueObjects\GeminiCachedObject;
use PrismPHP\Prism\Stream\Request as StreamRequest;
use PrismPHP\Prism\Structured\Request as StructuredRequest;
use PrismPHP\Prism\Structured\Response as StructuredResponse;
use PrismPHP\Prism\Text\Request as TextRequest;
use PrismPHP\Prism\Text\Response as TextResponse;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;

readonly class Gemini implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $url,
    ) {}

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        $handler = new Text(
            $this->client($request->clientOptions(), $request->clientRetry()),
            $this->apiKey
        );

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        $handler = new Structured($this->client(
            $request->clientOptions(),
            $request->clientRetry()
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $handler = new Embeddings($this->client(
            $request->clientOptions(),
            $request->clientRetry()
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function stream(StreamRequest $request): Generator
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    /**
     * @param  Message[]  $messages
     * @param  array<SystemMessage|string>  $systemPrompts
     */
    public function cache(string $model, array $messages = [], array $systemPrompts = [], ?int $ttl = null): GeminiCachedObject
    {
        if ($messages === [] && $systemPrompts === []) {
            throw new PrismException('At least one message or system prompt must be provided');
        }

        $systemPrompts = array_map(
            fn ($prompt): SystemMessage => $prompt instanceof SystemMessage ? $prompt : new SystemMessage($prompt),
            $systemPrompts
        );

        $handler = new Cache(
            client: $this->client(
                baseUrl: 'https://generativelanguage.googleapis.com/v1beta'
            ),
            model: $model,
            messages: $messages,
            systemPrompts: $systemPrompts,
            ttl: $ttl
        );

        return $handler->handle();
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<mixed>  $retry
     */
    protected function client(array $options = [], array $retry = [], ?string $baseUrl = null): PendingRequest
    {
        $baseUrl ??= $this->url;

        $client = Http::withOptions($options)
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
            ])
            ->baseUrl($baseUrl);

        if ($retry !== []) {
            return $client->retry(...$retry);
        }

        return $client;
    }
}
