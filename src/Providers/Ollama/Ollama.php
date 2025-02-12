<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingsRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Providers\Ollama\Handlers\Embeddings;
use EchoLabs\Prism\Providers\Ollama\Handlers\Structured;
use EchoLabs\Prism\Providers\Ollama\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class Ollama implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $url,
    ) {}

    #[\Override]
    public function text(TextRequest $request, int $currentStep): ProviderResponse
    {
        $handler = new Text($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

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
    public function embeddings(EmbeddingsRequest $request): EmbeddingsResponse
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
        return Http::withHeaders(array_filter([
            'Authorization' => $this->apiKey !== '' && $this->apiKey !== '0' ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl($this->url);
    }
}
