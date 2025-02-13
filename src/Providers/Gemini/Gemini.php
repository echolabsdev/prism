<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Providers\Gemini\Handlers\Embeddings;
use EchoLabs\Prism\Providers\Gemini\Handlers\Structured;
use EchoLabs\Prism\Providers\Gemini\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class Gemini implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $url,
    ) {}

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $handler = new Text(
            $this->client($request->clientOptions, $request->clientRetry),
            $this->apiKey
        );

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        $handler = new Structured($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $handler = new Embeddings($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<mixed>  $retry
     */
    protected function client(array $options = [], array $retry = []): PendingRequest
    {
        return Http::withOptions($options)
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
            ])
            ->retry(...$retry)
            ->baseUrl($this->url);
    }
}
